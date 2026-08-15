import { defineConfig } from 'astro/config'
import { unified } from '@astrojs/markdown-remark'
import mdx from '@astrojs/mdx'
import sitemap from '@astrojs/sitemap'
import rehypeAutolinkHeadings from 'rehype-autolink-headings'
import rehypeSlug from 'rehype-slug'

const base = '/ux-driver'

/** Depth-first map over every element node, replacing whatever the visitor returns. */
function mapElements(tree, visit) {
  const walk = (node) => {
    if (!Array.isArray(node.children)) return

    node.children = node.children.map((child) => {
      walk(child)

      return child.type === 'element' ? visit(child) : child
    })
  }

  walk(tree)
}

/**
 * Markdown pipe tables render a bare `<table>`, which cannot scroll on narrow
 * viewports. Wrap every one in a labelled scroll container.
 */
function rehypeWrapTables() {
  return (tree) =>
    mapElements(tree, (node) =>
      node.tagName === 'table'
        ? {
            type: 'element',
            tagName: 'div',
            properties: {
              className: ['table-scroll'],
              tabindex: '0',
              role: 'region',
              ariaLabel: 'Table, scrollable',
            },
            children: [node],
          }
        : node,
    )
}

/**
 * The site is served from a sub-path on GitHub Pages, but `base` is a build
 * setting no author should have to repeat in prose. Pages link with root-relative
 * hrefs (`/guides/security/`) and this rewrites them on the way out.
 */
function rehypeBaseLinks() {
  return (tree) =>
    mapElements(tree, (node) => {
      const href = node.properties?.href

      if (node.tagName === 'a' && typeof href === 'string' && href.startsWith('/') && !href.startsWith(`${base}/`)) {
        node.properties.href = `${base}${href}`
      }

      return node
    })
}

const rehypePlugins = [
  rehypeSlug,
  [
    rehypeAutolinkHeadings,
    {
      behavior: 'append',
      properties: {
        class: 'heading-anchor',
        ariaLabel: 'Link to this section',
      },
      // The glyph lives in CSS: a text node here would end up inside the heading
      // text Astro hands to the table of contents ("Trusted HTML#").
      content: [],
    },
  ],
  rehypeWrapTables,
  rehypeBaseLinks,
]

export default defineConfig({
  site: 'https://pentiminax.github.io',
  base,
  output: 'static',
  trailingSlash: 'always',
  integrations: [mdx(), sitemap()],
  markdown: {
    shikiConfig: {
      themes: {
        light: 'github-light',
        dark: 'github-dark',
      },
      defaultColor: false,
    },
    processor: unified({
      rehypePlugins,
    }),
  },
})
