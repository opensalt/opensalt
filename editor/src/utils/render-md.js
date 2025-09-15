import underline from 'markdown-it-underline';
import mk from '@vscode/markdown-it-katex';
import markdown from 'markdown-it';
import sanitizeHtml from 'sanitize-html';

const render = (function() {
    const
        md = markdown('default', {
            html: true,
            breaks: true,
            linkify: false
        }).use(underline).use(mk, {
            "throwOnError": false,
            "errorColor": " #cc0000"
        }),
        mdInline = markdown('default', {
            html: true,
            breaks: false,
            linkify: false
        }).use(underline).use(mk, {
            "throwOnError": false,
            "errorColor": " #cc0000"
        }),
        mdInlineLinked = markdown('default', {
            html: true,
            breaks: false,
            linkify: true
        }).use(underline).use(mk, {
            "throwOnError": false,
            "errorColor": " #cc0000"
        });

    function sanitizerBlock(dirty) {
        return sanitizeHtml(dirty, {
            allowedTags: [
                'ul', 'ol', 'li',
                'u', 'b', 'i',
                'br', 'p',
                'sup', 'sub',
                'img'
            ],
            allowedAttributes: {
                'ol': ['type'],
                'img': ['src', 'alt', 'title']
            }
        });
    }

    function sanitizerInline(dirty) {
        return sanitizeHtml(dirty, {
            allowedTags: ['img'],
            allowedAttributes: {'img': ['alt', 'title']}
        });
    }

    const render = {
        block: function(value) {
            return md.render(sanitizerBlock(value));
        },

        inline: function(value) {
            // Remove images and replace with alt text or "[image]"
            let htmlString = mdInline.renderInline(sanitizerInline(value));
            let parser = new DOMParser();
            let doc = parser.parseFromString('<div>' + htmlString + '</div>', 'text/html');
            let div = doc.querySelector('div');
            let imgs = div.querySelectorAll('img');
            imgs.forEach(img => {
                let text = '[' + (img.alt || img.title || 'image') + ']';
                let textNode = doc.createTextNode(text);
                img.parentNode.replaceChild(textNode, img);
            });
            return div.innerHTML;
        },

        inlineLinked: function(value) {
            // Remove images and replace with alt text or "[image]"
            let htmlString = mdInlineLinked.renderInline(sanitizerInline(value));
            let parser = new DOMParser();
            let doc = parser.parseFromString('<div>' + htmlString + '</div>', 'text/html');
            let div = doc.querySelector('div');
            let imgs = div.querySelectorAll('img');
            imgs.forEach(img => {
                let text = '[' + (img.alt || img.title || 'image') + ']';
                let textNode = doc.createTextNode(text);
                img.parentNode.replaceChild(textNode, img);
            });
            return div.innerHTML;
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

            return String(value).replace(/[&<>"'`=\/]/g, function(s) {
                return entityMap[s];
            });
        }
    };

    return render;
})();

export default render;
