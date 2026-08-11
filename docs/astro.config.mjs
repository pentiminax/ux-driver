import { defineConfig } from 'astro/config'
import { unified } from '@astrojs/markdown-remark'
import mdx from '@astrojs/mdx'
import sitemap from '@astrojs/sitemap'
import tailwindcss from '@tailwindcss/vite'
import rehypeAutolinkHeadings from 'rehype-autolink-headings'

export default defineConfig({
  site: 'https://pentiminax.github.io',
  base: '/ux-driver',
  output: 'static',
  trailingSlash: 'always',
  integrations: [mdx(), sitemap()],
  markdown: {
    shikiConfig: {
      theme: 'github-dark',
    },
    processor: unified({
      rehypePlugins: [
        [
          rehypeAutolinkHeadings,
          {
            behavior: 'append',
            properties: {
              class: 'heading-anchor',
              ariaLabel: 'Link to this section',
            },
            content: {
              type: 'element',
              tagName: 'span',
              properties: { ariaHidden: 'true' },
              children: [{ type: 'text', value: '#' }],
            },
          },
        ],
      ],
    }),
  },
  vite: {
    plugins: [tailwindcss()],
  },
})
