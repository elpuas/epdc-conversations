# CTA Button Design System Refresh

## Summary

Refreshed the shared EPDC Conversations WhatsApp CTA button system to feel more premium and conversion-focused while preserving the existing renderer, template, shortcode, floating button, and Gutenberg block architecture.

## Design Decisions

- Kept one shared frontend template and one shared stylesheet so floating, shortcode, and block output remain visually aligned.
- Reworked the base CTA into a rounded pill button with subtle gradient, restrained shadow, stronger typography, and more deliberate spacing.
- Replaced the previous icon markup with a dedicated inline WhatsApp SVG so there is no dependency on icon fonts or external assets.
- Applied a single state model for hover, focus-visible, mobile sizing, and reduced-motion behavior.
- Preserved backward compatibility for legacy `inline` variant input by normalizing it to `compact`.

## New Variants

- `default`: standard premium CTA with label and optional icon.
- `compact`: tighter spacing for denser layouts while keeping the same shared interaction model.
- `icon-only`: circular CTA that hides the visible label but keeps the accessible name.

## Size Support

- Added `small`, `medium`, and `large` size variants.
- Sizes are implemented through shared CSS custom properties to keep the stylesheet lightweight.

## Accessibility Improvements

- Decorative SVG icons use `aria-hidden="true"`.
- Icon-only buttons keep the label as visually hidden text and retain the existing descriptive `aria-label`.
- Focus-visible state now uses a clearer ring and offset treatment.
- Reduced-motion users keep the same CTA without animation transitions.
- Shared sizing maintains tap-friendly controls on mobile.

## Settings And Controls

- Added floating button settings for default variant, default size, and icon visibility.
- Added Gutenberg block controls for variant, size, and icon visibility.
- Added shortcode support for `size` alongside `variant` and `show_icon`.

## Remaining Limitations

- The editor preview still relies on server-side rendering and does not expose a live visual control UI beyond the existing inspector controls.
- The block attribute model still stores explicit style choices per block instance rather than inheriting floating-button defaults automatically.
