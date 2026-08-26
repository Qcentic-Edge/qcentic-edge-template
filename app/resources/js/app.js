import './bootstrap';
import hljs from 'highlight.js/lib/core';
import bash from 'highlight.js/lib/languages/bash';
import css from 'highlight.js/lib/languages/css';
import javascript from 'highlight.js/lib/languages/javascript';
import json from 'highlight.js/lib/languages/json';
import markdown from 'highlight.js/lib/languages/markdown';
import php from 'highlight.js/lib/languages/php';
import shell from 'highlight.js/lib/languages/shell';
import sql from 'highlight.js/lib/languages/sql';
import typescript from 'highlight.js/lib/languages/typescript';
import xml from 'highlight.js/lib/languages/xml';
import 'highlight.js/styles/github-dark.min.css';

hljs.registerLanguage('bash', bash);
hljs.registerLanguage('css', css);
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('js', javascript);
hljs.registerLanguage('json', json);
hljs.registerLanguage('markdown', markdown);
hljs.registerLanguage('md', markdown);
hljs.registerLanguage('php', php);
hljs.registerLanguage('shell', shell);
hljs.registerLanguage('sh', shell);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('ts', typescript);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('html', xml);
hljs.registerLanguage('blade', xml);

function enhanceCodeBlocks(root = document) {
    root.querySelectorAll('.site-prose pre > code').forEach((code) => {
        const pre = code.parentElement;

        if (!(pre instanceof HTMLPreElement) || pre.dataset.enhanced === '1') {
            return;
        }

        pre.dataset.enhanced = '1';

        if (!code.className.includes('hljs')) {
            hljs.highlightElement(code);
        }

        const wrap = document.createElement('div');
        wrap.className = 'site-code';
        pre.parentNode?.insertBefore(wrap, pre);
        wrap.appendChild(pre);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'site-code__copy';
        button.textContent = 'Copy';
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(code.textContent ?? '');
                button.textContent = 'Copied';
                window.setTimeout(() => {
                    button.textContent = 'Copy';
                }, 1600);
            } catch {
                button.textContent = 'Failed';
                window.setTimeout(() => {
                    button.textContent = 'Copy';
                }, 1600);
            }
        });
        wrap.appendChild(button);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => enhanceCodeBlocks());
} else {
    enhanceCodeBlocks();
}

// Filament/Alpine Navigate swaps the DOM on client-side navigations; DOMContentLoaded
// does not fire again, so re-run enhancement after each navigation.
document.addEventListener('alpine:navigated', () => enhanceCodeBlocks());
