import assert from 'node:assert/strict'
import { readFile, readdir } from 'node:fs/promises'
import { existsSync } from 'node:fs'
import test from 'node:test'

const dist = new URL('../dist/', import.meta.url)

async function readDistFile(path) {
  return readFile(new URL(path, dist), 'utf8')
}

test('built docs match the GitHub Pages contract', async () => {
  assert.equal(existsSync(dist), true, 'run npm run build before npm run test:build')

  const index = await readDistFile('index.html')
  const intro = await readDistFile('getting-started/introduction/index.html')
  const sitemap = await readDistFile('sitemap-index.xml')
  const pagefindFiles = await readdir(new URL('pagefind/', dist))
  const output = [index, intro, sitemap].join('\n')

  assert.match(index, /UX Driver/)
  assert.match(index, /Symfony UX product tours/)
  assert.match(index, /href="\/ux-driver\/getting-started\/introduction\/"/)
  assert.match(index, /Start tour/)
  assert.match(index, /Highlight/)
  assert.match(index, /Show hints/)
  assert.match(index, /<noscript>[\s\S]*interactive demo needs JavaScript/)
  assert.match(output, /aria-label="Documentation navigation"/)
  assert.match(output, /data-pagefind-search/)
  assert.match(output, /aria-label="Toggle theme"/)
  assert.match(intro, /Installation placeholder/)
  assert.match(intro, /class="heading-anchor"/)
  assert.match(intro, /href="#installation-placeholder"/)
  assert.match(intro, /aria-label="Link to this section"/)
  assert.match(output, /\/ux-driver\//)
  assert.match(sitemap, /https:\/\/pentiminax\.github\.io\/ux-driver\//)
  assert.ok(pagefindFiles.includes('pagefind.js'))
  assert.doesNotMatch(output, /__VITE_PRELOAD__/)
  assert.doesNotMatch(output, /ux-datatables|ux-sweet-alert/)
})
