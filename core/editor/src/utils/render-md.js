
import underline from 'markdown-it-underline';
import mkModule from '@vscode/markdown-it-katex';
import markdown from 'markdown-it';
import sanitizeHtml from 'sanitize-html';

const mk = mkModule?.default || mkModule;

const render = (function () {
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
                'img',
                'span', 'div',
                'strong', 'em',
                'a',
                'table', 'thead', 'tbody', 'tr', 'th', 'td',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'blockquote', 'pre', 'code'
            ],
            allowedAttributes: {
                'a': ['href', 'title', 'target'],
                'ol': ['type'],
                'img': ['src', 'alt', 'title'],
                'span': ['class', 'style'],
                '*': ['class']
            },
            allowedSchemes: ['http', 'https', 'mailto', 'data']
        });
    }

    function sanitizerInline(dirty) {
        return sanitizeHtml(dirty, {
            allowedTags: ['img', 'span', 'strong', 'em', 'b', 'i', 'u', 'a', 'code', 'sup', 'sub', 'br'],
            allowedAttributes: {
                'a': ['href', 'title', 'target'],
                'img': ['src', 'alt', 'title'],
                'span': ['class', 'style']
            },
            allowedSchemes: ['http', 'https', 'mailto', 'data']
        });
    }

    const render = {
        block: function (value) {
            return md.render(sanitizerBlock(value));
        },

        inline: function (value) {
            // Remove images and replace with alt text or "[image]"
            let htmlString = mdInline.renderInline(sanitizerInline(value));
            // Basic DOM parsing to replace images - OK in browser
            try {
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
            } catch (e) {
                return htmlString;
            }
        },

        inlineLinked: function (value) {
            // Remove images and replace with alt text or "[image]"
            let htmlString = mdInlineLinked.renderInline(sanitizerInline(value));
            try {
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
            } catch (e) {
                return htmlString;
            }
        },

        escaped: function (value) {
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

            return String(value).replace(/[&<>"'`=/]/g, function (s) {
                return entityMap[s];
            });
        }
    };

    return render;
})();

export default render;
