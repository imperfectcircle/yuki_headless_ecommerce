# Headless E-commerce Backoffice (Laravel + Inertia + React)

This project is a **headless e-commerce backoffice** built with **Laravel 12**, **Inertia.js** and **React**.

The goal is to provide a **scalable, backend-driven e-commerce engine** that can be reused across multiple storefronts, while keeping a powerful and developer-friendly admin panel.

The frontend store (B2C) is intentionally **decoupled** and can be implemented using any technology (Next.js, Nuxt, mobile apps, etc.), consuming the exposed APIs.

---

## Project Goals

-   Headless, API-first architecture
-   Backend-driven business logic
-   Reusable across multiple e-commerce projects
-   Clear domain separation
-   Easy to extend and maintain over time
-   Admin panel built with Inertia + React
-   Frontend-agnostic storefronts

---

## Tech Stack

### Backend

-   **Laravel 12**
-   MySQL / PostgreSQL
-   REST API (future-ready for GraphQL if needed)

### Admin Panel

-   **Inertia.js**
-   **React**
-   Tailwind CSS

### Authentication

-   Laravel Breeze (admin only)

---

## Architecture Overview

This project follows a **backend-driven, domain-oriented architecture**.

-   The **admin panel** is the only frontend included in this repository
-   The **storefront is external** and consumes APIs
-   All business rules live in the backend

Backend (Laravel)
├── Domains
│ ├── Catalog
│ ├── Pricing
│ ├── Inventory
│ ├── Orders
│ └── ...
├── Http
│ ├── Controllers (Admin / API)
│ └── Requests
├── Models
└── Actions (Domain logic)

> Note: Models currently live in `app/Models`.  
> Domain folders are progressively introduced where business logic grows.

---

## Core Concepts

### Products & Variants

-   A **Product** represents a conceptual item (e.g. "T-Shirt")
-   A **Product Variant** represents a sellable item (e.g. "T-Shirt / Black / M")
-   Every product **must have at least one variant**
-   Variants are the single source of truth for:
    -   Pricing
    -   Inventory
    -   SKU

This design avoids edge cases and scales naturally to complex catalogs.

---

### Pricing (Current State)

-   Prices are stored as **integers** (minor units, e.g. cents)
-   A base currency is currently assumed
-   Multi-currency support is planned as a first-class feature

---

## Admin Panel

The admin panel allows administrators to:

-   Create and manage products
-   Define product variants
-   Assign prices to variants
-   Manage catalog data

The admin UI is built using **Inertia + React**, keeping backend and frontend tightly aligned without exposing APIs internally.

---

## API Layer

The API layer is designed to:

-   Serve one or more external storefronts
-   Be independent from the admin panel
-   Expose only validated, normalized data

API endpoints are versionable and structured to evolve without breaking consumers.

---

## Current Status

✅ Project bootstrapped  
✅ Authentication (admin)  
✅ Catalog domain foundation  
✅ Products & variants schema  
✅ Base pricing model  
✅ Admin product creation flow

🚧 In progress:

-   Variant attributes & options
-   Inventory management
-   Multi-currency pricing
-   Checkout & orders
-   Payments & shipping integrations

---

## Installation

```bash
git clone <repository-url>
cd project-name

composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate
npm run dev
php artisan serve
```

## Philosophy

This project is not a “ready-made shop”.

It is a foundation:

opinionated where needed

flexible where it matters

designed for developers building real-world e-commerce solutions

## License

This project is open-source and licensed under the MIT license.
