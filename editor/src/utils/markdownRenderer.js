import MarkdownIt from 'markdown-it';
import markdownItKatex from '@vscode/markdown-it-katex';
import markdownItUnderline from 'markdown-it-underline';
import sanitizeHtml from 'sanitize-html';

// Configure markdown-it with plugins
const md = new MarkdownIt({
  html: true,
  linkify: true,
  typographer: true,
  breaks: true
})
.use(markdownItKatex, {
  throwOnError: false,
  errorColor: '#cc0000'
})
.use(markdownItUnderline);

// Sanitization options for rendered HTML
const sanitizeOptions = {
  allowedTags: [
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'p', 'br', 'hr',
    'strong', 'b', 'em', 'i', 'u', 's', 'strike',
    'ul', 'ol', 'li',
    'blockquote', 'code', 'pre',
    'a', 'img',
    'table', 'thead', 'tbody', 'tr', 'th', 'td',
    'span', 'div'
  ],
  allowedAttributes: {
    'a': ['href', 'title', 'target'],
    'img': ['src', 'alt', 'title'],
    'span': ['class'],
    'div': ['class'],
    'code': ['class'],
    'pre': ['class'],
    '*': ['style']
  },
  allowedStyles: {
    '*': {
      'color': [/^#(0x)?[0-9a-f]+$/i, /^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/],
      'background-color': [/^#(0x)?[0-9a-f]+$/i, /^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/],
      'text-align': [/^left$/, /^right$/, /^center$/],
      'font-size': [/^\d+(?:px|em|%)$/],
      'font-weight': [/^\d+$/, /^bold$/],
      'text-decoration': [/^underline$/, /^line-through$/]
    }
  },
  allowedClasses: {
    'span': ['katex', 'katex-mathml'],
    'div': ['katex-display'],
    'code': ['language-*'],
    'pre': ['language-*']
  }
};

/**
 * Renders markdown text to sanitized HTML
 * @param {string} markdownText - The markdown text to render
 * @returns {string} - The sanitized HTML
 */
export function renderMarkdown(markdownText) {
  if (!markdownText || typeof markdownText !== 'string') {
    return '';
  }

  try {
    // Render markdown to HTML
    const html = md.render(markdownText);

    // Sanitize the HTML
    const sanitizedHtml = sanitizeHtml(html, sanitizeOptions);

    return sanitizedHtml;
  } catch (error) {
    console.error('Error rendering markdown:', error);
    // Return the original text if rendering fails
    return markdownText;
  }
}

/**
 * Checks if text contains markdown syntax
 * @param {string} text - The text to check
 * @returns {boolean} - True if text contains markdown
 */
export function hasMarkdown(text) {
  if (!text || typeof text !== 'string') {
    return false;
  }

  // Common markdown patterns
  const markdownPatterns = [
    /^\s*#+\s/m,           // Headers
    /\*\*.*?\*\*/,         // Bold
    /\*.*?\*/,             // Italic
    /_.*?_/,               // Underline/Italic
    /`.*?`/,               // Inline code
    /```[\s\S]*?```/m,     // Code blocks
    /\[.*?\]\(.*?\)/,      // Links
    /!\[.*?\]\(.*?\)/,     // Images
    /^\s*[-*+]\s/m,        // Unordered lists
    /^\s*\d+\.\s/m,        // Ordered lists
    /^\s*>\s/m,            // Blockquotes
    /\$\$[\s\S]*?\$\$/m,   // KaTeX display math
    /\$[\s\S]*?\$/,        // KaTeX inline math
  ];

  return markdownPatterns.some(pattern => pattern.test(text));
}

export default {
  renderMarkdown,
  hasMarkdown
};
