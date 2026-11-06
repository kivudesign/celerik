# Content Security Policy

Components write no inline scripts or styles, so a strict policy works. Pass a nonce to the assets tag:

::: code-group

```blade [Blade]
<x-celerik-assets :nonce="$nonce" />
```

```twig [Twig]
{{ celerik_assets(nonce: nonce) }}
```

```php [Plain PHP]
<?= Assets::tags('/vendor/celerik', nonce: $nonce) ?>
```

:::

The nonce goes on the module script, and is printed as `<meta name="csp-nonce">`, which FullCalendar uses for the styles it injects.

## Tested policy

```text
default-src 'self'; script-src 'self' 'nonce-…'; style-src 'self' 'nonce-…';
img-src 'self' data:; font-src 'self' data:; connect-src 'self'
```

- `font-src data:` is needed for the Scheduler's toolbar icons.
- Add your CDN to `script-src` and `style-src` if Bootstrap and jQuery come from one, and `https://cdn.jsdelivr.net` if the Celerik bundle does (`Assets::cdnUrl()`).
- The File Manager also needs `style-src-attr 'unsafe-inline'`: elFinder builds its interface with inline `style` attributes. That allows style attributes only (they cannot run code); scripts and `<style>` elements still need the nonce.
- The ThemeBuilder previews themes with a constructed stylesheet, which needs no change to the policy.
