<?php

namespace Korneil\JTMPAsseticFilterBundle\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;

class JTMP implements FilterInterface
{

    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        $data = $asset->getContent();

        $data = preg_replace_callback('/<jtmp>(.*?)<\/jtmp>/sm', array($this, 'parseJTMPTag'), $data);

        $asset->setContent($data);
    }

    private function parseJTMPTag($match)
    {
        return $this->compileJTmp($match[1]);
    }

    private function compileJTmp($jData, $breakLines = false)
    {
        $lb = $breakLines ? "\n" : '';

        $res = '{' . $lb;
        $cB = preg_match_all('/_\{(.*?)\}(.*?)_\{\}/sm', $jData, $blocks);
        for ($i = 0; $i < $cB; $i++) {
            $h = explode("\n", $blocks[2][$i]);
            $x = array();
            foreach ($h as &$px) {
                $px = trim($px);
                if ($px != '') {
                    $x[] = $px;
                }
            }
            $x = implode(' ', $x);

            $cM = preg_match_all('/([\$|@|\?|\!|\^]){(.*?)\}/sm', $x, $m, PREG_OFFSET_CAPTURE);

            $res .= '"' . $blocks[1][$i] . '":function(x){r="";' . $lb;
            for ($j = 0; $j < $cM; $j++) {
                $pos = ($j == 0 ? 0 : $m[0][$j - 1][1] + strlen($m[0][$j - 1][0]));
                $before = $this->solidTrim(substr($x, $pos, $m[0][$j][1] - $pos));
                if (strlen($before)) {
                    $res .= 'r+="' . addslashes($before) . '";' . $lb;
                }
                switch ($m[1][$j][0]) {
                    case '$':
                        $h = explode('|', $m[2][$j][0]);
                        $h[0] = $this->createJTmpVar($h[0]);
                        $res .= 'r+=' . $h[0] . ';' . $lb;
                        break;
                    case '@':
                        if ($m[2][$j][0] == '') {
                            $res .= '}' . $lb;
                        } else {
                            $h = explode('|', $m[2][$j][0]);
                            $h[0] = $this->createJTmpVar($h[0]);
                            $h[1] = $this->createJTmpVar($h[1]);
                            if (isset($h[2])) {
                                $res .= 'for(' . $h[1] . ' in ' . $h[0] . '){' .
                                    $this->createJTmpVar($h[2]) . '=' . $h[0] . '[' . $h[1] . '];' . $lb;
                            } else {
                                $res .= 'for(' . $h[1] . '=0;' . $h[1] . '<' . $h[0] . '.length;' . $h[1] . '++){' . $lb;
                            }
                        }
                        break;
                    case '?':
                        if ($m[2][$j][0] == '') {
                            $res .= '}' . $lb;
                        } else {
                            $res .= 'if(' . $this->createJTmpVar($m[2][$j][0]) . '){' . $lb;
                        }
                        break;
                    case '!':
                        $res .= '}else{' . $lb;
                        break;
                    case '^':
                        $res .= 'r+=this["' . $m[2][$j][0] . '"](x);' . $lb;
                        break;
                }
            }
            $h = $this->solidTrim(substr($x, $cM ? $m[0][$cM - 1][1] + strlen($m[0][$cM - 1][0]) : 0));
            if (strlen($h)) {
                $res .= 'r+="' . addslashes($h) . '";' . $lb;
            }
            $res .= 'return r;}' . $lb;
            if ($i < $cB - 1) {
                $res .= ',' . $lb;
            }
        }
        $res .= '}' . $lb;

        return $res;
    }

    private function createJTmpVar($x)
    {
        $cM = preg_match_all('/\"|(?:[a-zA-Z_][a-zA-Z_\.0-9]*)/', $x, $m, PREG_OFFSET_CAPTURE);
        $gPos = 0;
        $inQ = false;
        $res = '';
        foreach ($m[0] as $i => &$px) {
            if ($px[1] > 0 && $x[$px[1] - 1] == '.') {
                unset($m[0][$i]);
                $cM--;
            }
        }
        $m[0] = array_merge($m[0]);
        for ($i = 0; $i < $cM; $i++) {
            $pos = ($i == 0 ? 0 : $m[0][$i - 1][1] + strlen($m[0][$i - 1][0]));
            $res .= substr($x, $pos, $m[0][$i][1] - $pos);
            if ($m[0][$i][0] == '"') {
                $inQ = !$inQ;
                $res .= '"';
            } else {
                if (!$inQ) {
                    $res .= 'x.';
                    $gPos += 6;
                }
                $res .= $m[0][$i][0];
            }
        }
        $res .= substr($x, $cM ? $m[0][$cM - 1][1] + strlen($m[0][$cM - 1][0]) : 0);

        return $res;
    }

    private function solidTrim($s)
    {
        static $whiteSpaces = " \t\n\r\0\x0B";
        $len = strlen($s);
        $l = ($len > 0 && strpos($whiteSpaces, $s[0]) !== false) ? true : false;
        $r = ($len > 1 && strpos($whiteSpaces, $s[$len - 1]) !== false) ? true : false;

        return ($l ? ' ' : '') . trim($s) . ($r ? ' ' : '');
    }

}
