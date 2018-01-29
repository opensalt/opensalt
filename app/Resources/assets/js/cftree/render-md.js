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

    render.mde = function (element) {
        let SimpleMDE = require('simplemde');
        return new SimpleMDE({
            element: element,
            toolbar: [
                'bold', 'italic', 'heading', '|',
                'quote', 'unordered-list', 'ordered-list', '|',
                'table', 'horizontal-rule', '|',
                'preview', 'side-by-side', 'fullscreen'
            ],
            previewRender: render.block
        });
    };

    return render;
})();

module.exports = render;
