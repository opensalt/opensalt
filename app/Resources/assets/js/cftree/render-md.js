const render = (function(){
    let
        underline = require('markdown-it-underline'),
        mk = require('markdown-it-katex'),
        markdown = require('markdown-it'),
        md = markdown('default', {
            html: true,
            breaks: true,
            linkify: false
        }).use(underline).use(mk, {"throwOnError": false, "errorColor": " #cc0000"}),
        mdInline = markdown('default', {
            html: false,
            breaks: false,
            linkify: false
        }).use(underline).use(mk, {"throwOnError": false, "errorColor": " #cc0000"}),
        mdInlineLinked = markdown('default', {
            html: false,
            breaks: false,
            linkify: true
        }).use(underline).use(mk, {"throwOnError": false, "errorColor": " #cc0000"})
    ;

    function sanitizerBlock(dirty) {
        let sanitizeHtml = require('sanitize-html');

        return sanitizeHtml(dirty, {
            allowedTags: [
                'ul', 'ol', 'li',
                'u', 'b', 'i',
                'br', 'p',
                'sup', 'sub'
            ],
            allowedAttributes: {
                'ol': [ 'type' ]
            }
        });
    }

    function sanitizerInline(dirty) {
        let sanitizeHtml = require('sanitize-html');

        return sanitizeHtml(dirty, {
            allowedTags: [ ],
            allowedAttributes: { }
        });
    }

    let render = {
        block: function (value) {
            return md.render(sanitizerBlock(value));
        },

        inline: function (value) {
            return mdInline.renderInline(sanitizerInline(value));
        },

        inlineLinked: function (value) {
            return mdInlineLinked.renderInline(sanitizerInline(value));
        },

        escaped: function(value) {
            let entityMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            };

            return String(value).replace(/[&<>"'`=\/]/g, function (s) {
                return entityMap[s];
            });
        }
    };

    let createLevelOneAlphaList = function(listStartCount, startPoint,
        endPoint, cm, callbackFunction) {
      let levelOneEndpoint = endPoint;
      if (undefined !== callbackFunction) {
        levelOneEndpoint = startPoint + 25;
      }
      let counter = 0;
      for (let i = startPoint; i <= levelOneEndpoint; i++) {
        let text = cm.getLine(i);
        if (text.substring(0, 2) === String.fromCharCode(listStartCount
            + counter)
            + ".") {
          text = text.slice(3);
        } else {
          text = String.fromCharCode(listStartCount + counter) + ". "
          + text;
        }
        counter++;
        cm.replaceRange(text, {
          line : i,
          ch : 0
        }, {
          line : i,
          ch : 99999999999999
        });
      }
      if (undefined !== callbackFunction) {
        callbackFunction(listStartCount, startPoint, endPoint, cm);
      }
    };

    let createLevelTwoAlphaList = function(listStartCount, startPoint,
        endPoint, cm) {
      let lineCounter = parseInt(startPoint + 26);
      outerloop: for (let outerCount = 0; outerCount < 26; outerCount++) {
        for (let innerCounter = 0; innerCounter < 26; innerCounter++) {
          if (lineCounter > endPoint) {
            break outerloop;
          }
          let text = cm.getLine(lineCounter);
          if (text.substring(0, 3) === String.fromCharCode(listStartCount
              + outerCount)
              + String.fromCharCode(listStartCount + innerCounter)
              + ".") {
            text = text.slice(4);
          } else {
            text = String.fromCharCode(listStartCount + outerCount)
            + String
            .fromCharCode(listStartCount + innerCounter)
            + ". " + text;
          }
          cm.replaceRange(text, {
            line : lineCounter,
            ch : 0
          }, {
            line : lineCounter,
            ch : 99999999999999
          });
          lineCounter++;
        }
      }
    };

    let createLevelThreeAlphaList = function(listStartCount, startPoint,
        endPoint, cm) {
      let lineCounter = 0;
      outerloop: for (let outerMostCount = 0; outerMostCount < 26; outerMostCount++) {
        for (let outerCount = 0; outerCount < 26; outerCount++) {
          for (let innerCounter = 0; innerCounter < 26; innerCounter++) {
            if (lineCounter > endPoint) {
              break outerloop;
            }
            let text = cm.getLine(lineCounter);
            if (text.substring(0, 4) === String
                .fromCharCode(listStartCount + outerMostCount)
                + String.fromCharCode(listStartCount + outerCount)
                + String
                .fromCharCode(listStartCount + innerCounter)
                + ".") {
              text = text.slice(5);
            } else {
              text = String.fromCharCode(listStartCount
                  + outerMostCount)
                  + String.fromCharCode(listStartCount
                      + outerCount)
                      + String.fromCharCode(listStartCount
                          + innerCounter) + ". " + text;
            }
            cm.replaceRange(text, {
              line : lineCounter,
              ch : 0
            }, {
              line : lineCounter,
              ch : 99999999999999
            });
            lineCounter++;
          }
        }
      }
    };

    function alphaList(editor) {
      let cm = editor.codemirror;
      if (cm.getSelection()) {
        let startPoint = cm.getCursor("start");
        let endPoint = cm.getCursor("end");
        let listStartCount = 97;
        if (endPoint.line-startPoint.line > 675+25) {
          createLevelThreeAlphaList(listStartCount, startPoint.line,
              endPoint.line, cm);
        } else {
          if (endPoint.line - startPoint.line > 25) {
            createLevelOneAlphaList(listStartCount, startPoint.line,
                endPoint.line, cm, createLevelTwoAlphaList);
          } else {
            createLevelOneAlphaList(listStartCount, startPoint.line,
                endPoint.line, cm);
          }
        }
        cm.focus();
      }
    }

    render.mde = function (element) {
        let SimpleMDE = require('simplemde');
        return new SimpleMDE({
          element : element,
          toolbar : [ 'bold', 'italic', 'heading', '|', 'quote',
            'unordered-list', 'ordered-list', {
            name : "AlphabeticalList",
            action : alphaList,
            className : "fa fa-sort-alpha-asc", // Look for a suitable icon
            title : "Alphabetical List",
          } , '|', 'table', 'horizontal-rule', '|', 'preview',
          'side-by-side', 'fullscreen' ],
          previewRender : render.block
        });
    };

    return render;
})();

module.exports = render;
