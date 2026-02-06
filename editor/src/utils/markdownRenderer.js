 
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
    errorColor: '#cc0000',
    displayMode: true
  })
  .use(markdownItUnderline);

// Configure sanitize-html
const sanitizeOptions = {
  allowedTags: [
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'p', 'a', 'ul', 'ol',
    'nl', 'li', 'b', 'i', 'strong', 'em', 'strike', 'code', 'hr', 'br', 'div',
    'table', 'thead', 'caption', 'tbody', 'tr', 'th', 'td', 'pre', 'img',
    'span', 'sub', 'sup'
  ],
  allowedAttributes: {
    'a': ['href', 'name', 'target', 'title', 'class'],
    'img': ['src', 'alt', 'title', 'width', 'height', 'style', 'class'],
    '*': ['style', 'class', 'id']
  },
  allowedSchemes: ['http', 'https', 'ftp', 'mailto', 'data']
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

    // Sanitize HTML to prevent XSS attacks
    const sanitizedHtml = sanitizeHtml(html, sanitizeOptions);

    return sanitizedHtml;
  } catch (error) {
    console.error('Error rendering markdown:', error);
    // Return original text if rendering fails
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
