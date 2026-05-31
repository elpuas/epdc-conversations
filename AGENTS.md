# AGENTS.md

## EPDC WhatsApp

## Purpose

EPDC WhatsApp is a lightweight WordPress plugin focused on improving lead generation and conversion through WhatsApp.

The plugin provides customizable WhatsApp call-to-action components with conversion tracking, dynamic messaging, analytics hooks, and reusable configuration across client websites.

This plugin is intentionally focused only on WhatsApp because it is the primary communication channel used by most businesses and customers in Latin America.

The goal is not to create a chat platform.

The goal is to increase conversions.

⸻

## Development Principles

* PHP 8.3+
* WordPress Coding Standards
* PSR-4 autoloading
* Dependency Injection preferred
* Accessibility first
* Performance focused
* Minimal frontend JavaScript
* Server-side rendering whenever possible
* Avoid unnecessary abstractions
* Avoid over engineering

⸻

Core Features

Phase 1

* Floating WhatsApp button
* Global plugin settings
* Customizable styles and colors
* Dynamic message variables
* Shortcode support
* Gutenberg block
* Mobile/Desktop visibility controls
* Accessibility support
* Click tracking
* Conversion logs

Phase 2

* WooCommerce integration
* CTA variants
* Analytics dashboard
* Page-level overrides
* UTM tracking
* Lead attribution

⸻

## Conversion Tracking

The plugin should support interaction logging.

## Examples:

* Button clicks
* Current page URL
* Referrer
* Timestamp
* Device type
* UTM parameters
* Anonymous session identifier
* Hashed IP address

Avoid storing sensitive personal data directly whenever possible.

Use hashed IPs instead of raw IP addresses.

# Example:

hash( 'sha256', $ip )

⸻

## Dynamic Variables

Messages should support dynamic variables.

Examples:

* {site_name}
* {post_title}
* {post_url}
* {current_url}
* {product_title}
* {product_price}

Developers must be able to register custom variables using hooks.

⸻

## Accessibility Requirements

* WCAG 2.1 AA
* Keyboard accessible
* Proper ARIA labels
* Visible focus states
* Reduced motion support

⸻

## Frontend Rules

Use:

* Vanilla JavaScript or Interactivity API
* Modern CSS features like native nesting, :is(), :has(), etc.
* WordPress enqueue APIs

Avoid:

* jQuery
* Large frontend frameworks

⸻

## Extensibility

Expose hooks for:

* Message generation
* Button rendering
* Tracking events
* Analytics integrations
* Custom variables
* Custom styles

⸻

## Suggested Architecture

epdc-whatsapp/
├── assets/
├── includes/
├── src/
├── templates/
├── languages/
├── tests/
├── vendor/
├── composer.json
├── block.json
├── readme.txt
└── epdc-whatsapp.php

⸻

## Coding Standards

PHP

* Use strict types whenever possible
* Use short array syntax []
* Prefer early returns
* Avoid static utility classes unless justified
* Prefer constructor injection
* Function comments must always be in English

## JavaScript

* Use ES6+
* Use const and let
* No var
* Prefer function declarations
* Avoid unnecessary dependencies

## CSS

* Prefer vanilla CSS
* Modern CSS features are allowed
* Use maintainable class naming
* Avoid deeply nested selectors

⸻

## Plugin Philosophy

The plugin should remain:

* Simple
* Maintainable
* Fast
* Reusable
* Conversion-oriented

Every feature must justify its impact on lead generation, usability, or analytics.

## Git Workflow Rules

* Agents are allowed to create branches.
* Agents are allowed to create local commits.
* Agents must NEVER push to remote repositories.
* Agents must NEVER create pull requests automatically.
* All pushes and GitHub operations are handled manually by Alfredo.

⸻

## Context Logging

Every meaningful change must generate a log file inside:

.context/

Log filename format:

.context/YYYY-MM-DD-task-name.md

Example:

.context/2026-05-31-floating-button-initial-setup.md

⸻

Commit Rules

* Use small focused commits
* Avoid large unrelated commits
* Write clear commit messages

Examples:

feat: add floating whatsapp button renderer
fix: sanitize whatsapp settings values
refactor: move tracking logic into service class
