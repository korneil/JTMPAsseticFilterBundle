# JTMP Assetic Filter Bundle

A small javascript template engine, that creates JSON object from template, inlined in javascript between <jtmp> and </jtmp> tags.

## Install

    // composer.json
    "require": {
        ...
        "korneil/jtmp-assetic-filter-bundle": "dev-master",
        ...
    }


# Register

        // app/AppKernel.php
        $bundles = array(
            ...
            new Korneil\JTMPAsseticFilterBundle\KorneilJTMPAsseticFilterBundle(),
            ...
        );

# Usage

**Filter name:** jtmp

Example .js file:

  // .../app.js

    var tmp=<jtmp>
      _{main}
        Name: ${name}

        ?{condition}
          true
        ?{}

        ?{greaterThanOne>1}
          Greater than one.
        !{}
          Not greater than one.
        ?{}

        @{keyvalue|key|value}
          ${key}: ${value};
        @{}

        Embed: ^{other}
      _{}

      _{other}
      You can grab this text by invoking tmp.other(); Right ${name}?
      _{}
    </jtmp>;

    var result=tmp.main({
      name:"neil",
      numbers:[1,2,3],
      condition:true,
      greaterThanOne:2,
      keyvalue:{
        one:1,
        two:2
      }
    });

    console.log(result);

Twig:

    {% javascripts filter="jtmp"
        '@AcmeBundle/Resources/public/js/*.js'
        %}
        <script type="text/javascript" src="{{ asset_url }}"></script>
    {% endjavascripts %}


Output:

    Name: neil  true   Greater than one.   one: 1;  two: 2;  Embed: You can grab this text by invoking tmp.other(); Right neil?

Generated .js file:

    var tmp={"main":function(x){r="";r+="Name: ";r+=x.name;r+=" ";if(x.condition){r+=" true ";}r+=" ";if(x.greaterThanOne>1){r+=" Greater than one. ";}else{r+=" Not greater than one. ";}r+=" ";for(x.key in x.keyvalue){x.value=x.keyvalue[x.key];r+=" ";r+=x.key;r+=": ";r+=x.value;r+="; ";}r+=" Embed: ";r+=this["other"](x);return r;},"other":function(x){r="";r+="You can grab this text by invoking tmp.other(); Right ";r+=x.name;r+="?";return r;}};

    var result=tmp.main({
      name:"neil",
      numbers:[1,2,3],
      condition:true,
      greaterThanOne:2,
      keyvalue:{
        one:1,
        two:2
      }
    });

    console.log(result);

