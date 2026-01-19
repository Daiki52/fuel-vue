# Vue.js 3 FuelPHP Adapter

A FuelPHP adapter for integrating Vue.js 3 into existing applications. It aims to make it straightforward to build modern, reactive user interfaces on top of FuelPHP while keeping your current server-side routing and controller patterns.

This library is inspired by **@inertiajs/inertia-vue3**. We also hope it can serve as a practical stepping stone for teams that want to modernize incrementally and, where appropriate, ease a future migration to modern frameworks such as **Laravel**.

---

## Motivation

Many production systems still run on FuelPHP, while frontend expectations have shifted toward Vue.js 3, Vite, and component-driven development. This adapter is designed to help you adopt that modern frontend workflow without requiring an immediate full rewrite or a hard switch to a different backend framework.

---

## Features

- Easy integration of Vue.js 3 components into FuelPHP
- Inertia-style development experience (page-driven UI) with FuelPHP
- Flexible configuration options
- Designed to be compatible with existing FuelPHP applications
- Supports incremental adoption (partial replacement and coexistence with server-rendered views)

---

## Requirements

- PHP: 7.0 ~ 7.4
- FuelPHP: 1.8 (Recommended)
- **Vite** is required for building and bundling your Vue.js components

---

## Installation

```bash
composer require Daiki52/fuel-vue
```
