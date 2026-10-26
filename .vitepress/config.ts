import { defineConfig } from 'vitepress'

// The markdown in this repository is the source: the site is a view of it, not a copy.
// Everything links the same way on GitHub and on the site, so srcDir is the repository root.
export default defineConfig({
  title: 'Laravel QR',
  description: 'QR codes for Laravel.',
  base: '/laravel-qr/',
  lastUpdated: true,
  head: [['link', { rel: 'icon', type: 'image/svg+xml', href: '/laravel-qr/logo.svg' }]],
  cleanUrls: true,

  srcExclude: [
    'vendor/**',
    'node_modules/**',
    'tests/**',
    'workbench/**',
    'docker/**',
    'config/**',
    'build/**',
    'resources/**',
  ],

  // README.md is the front page here, and stays the front page on GitHub.
  rewrites: {
    'README.md': 'index.md',
    'docs/README.md': 'docs/index.md',
  },

  vite: {
    server: {
      watch: {
        ignored: ['**/vendor/**', '**/workbench/**', '**/tests/**', '**/.phpunit.cache/**'],
      },
    },
  },

  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Documentation', link: '/docs/' },
      { text: 'Packagist', link: 'https://packagist.org/packages/exeque/laravel-qr' },
    ],

    sidebar: [
      {
        text: 'Getting started',
        items: [
          { text: 'Introduction', link: '/' },
          { text: 'Installation', link: '/docs/installation' },
          { text: 'Getting started', link: '/docs/getting-started' },
          { text: 'Documentation index', link: '/docs/' },
        ],
      },
      {
        text: 'Making codes',
        items: [
          { text: 'Content', link: '/docs/content' },
          { text: 'Output', link: '/docs/output' },
          { text: 'Styling', link: '/docs/styling' },
          { text: 'Blade', link: '/docs/blade' },
          { text: 'Validating input', link: '/docs/validation' },
        ],
      },
      {
        text: 'Looks',
        items: [
          { text: 'Modules', link: '/docs/modules' },
          { text: 'Eyes', link: '/docs/eyes' },
          { text: 'Colors', link: '/docs/colors' },
          { text: 'Gradients', link: '/docs/gradients' },
          { text: 'Filters', link: '/docs/filters' },
          { text: 'Logos', link: '/docs/logos' },
          { text: 'Scan safety', link: '/docs/scan-safety' },
          { text: 'Shape icons', link: '/docs/icons' },
        ],
      },
      {
        text: 'Around it',
        items: [
          { text: 'Reading codes', link: '/docs/reading' },
          { text: 'Caching', link: '/docs/caching' },
          { text: 'Testing', link: '/docs/testing' },
          { text: 'Events', link: '/docs/events' },
          { text: 'Monocle', link: '/docs/monocle' },
          { text: 'Artisan commands', link: '/docs/commands' },
          { text: 'Configuration', link: '/docs/configuration' },
          { text: 'Extending', link: '/docs/extending' },
        ],
      },
      {
        text: 'Contributing',
        items: [
          { text: 'Contributing', link: '/CONTRIBUTING' },
        ],
      },
    ],

    search: {
      provider: 'local',
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/ExeQue/laravel-qr' },
    ],

    editLink: {
      pattern: 'https://github.com/ExeQue/laravel-qr/edit/main/:path',
      text: 'Edit this page on GitHub',
    },

    outline: [2, 3],
  },
})
