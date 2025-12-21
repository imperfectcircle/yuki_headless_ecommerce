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

```
Backend (Laravel)
├── Domains
│   ├── Catalog
│   │   ├── Models (Product, ProductVariant, Attribute, AttributeOption)
│   │   ├── Actions (GenerateProductVariants)
│   │   └── ...
│   ├── Pricing
│   │   ├── Models (Price, Currency)
│   │   ├── Actions (SetVariantPrice)
│   │   └── ...
│   ├── Inventory
│   │   ├── Models (Inventory)
│   │   ├── Actions (ReserveInventory, ReleaseInventory, ConfirmInventory)
│   │   └── ...
│   ├── Orders
│   │   ├── Models (Order, OrderItem)
│   │   ├── Actions (CreateOrder, ConfirmInventory)
│   │   └── ...
│   └── ...
├── Http
│   ├── Controllers (Admin / API)
│   └── Requests
└── ...
```

### Explanation

-   **Domains**: Contains domain-specific logic grouped by business area.
-   **Models**: Eloquent models representing database entities, now organized per domain
-   **Actions**: Encapsulate all domain business logic
-   **Http**: Controllers and request validation.

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
-   Multi-currency support is implemented via the Currency model
-   Each variant can have multiple prices in different currencies, with validity ranges and VAT rates
-   Prices are linked to variants via a polymorphic relation

---

### Inventory

-   Each ProductVariant has an associated inventory record
-   Inventory fields:
    -   quantity: total stock
    -   reserved: stock currently reserved
    -   backorder_allowed: optional, configurable per variant
-   Actions:
    -   ReserveInventory → reserves stock for a given quantity
    -   ReleaseInventory → releases reserved stock safely
    -   ConfirmInventory → finalizes inventory consumption upon order confirmation

---

### Orders & OrderItems

-   Order represents a customer purchase
-   OrderItem stores a snapshot of the variant at the time of purchase:
    -   SKU
    -   Name
    -   Attributes
    -   Price per unit
    -   Quantity
    -   Total
-   Orders store monetary and customer snapshots, ensuring historical consistency
-   Actions:
    -   CreateOrder → creates order + items with calculated totals
    -   ConfirmInventory → decrements stock based on order items, respecting reserved quantities and backorder rules

---

### Inventory & Order Flow

```
+--------------------+
|  Customer adds     |
|  items to cart     |
+--------------------+
          |
          v
+--------------------+
|  CreateOrder       |
|  (Order draft)     |
|  + snapshot items  |
+--------------------+
          |
          v
+--------------------+
|  ReserveInventory  |
|  - check stock     |
|  - reserve qty     |
|  - backorder opt.  |
+--------------------+
          |
          v
+--------------------+
|  Payment Success   |
+--------------------+
          |
          v
+--------------------+
|  ConfirmInventory  |
|  - decrement stock |
|  - mark confirmed  |
|  - atomic tx       |
+--------------------+
          |
          v
+--------------------+
|  Order status =    |
|  confirmed         |
+--------------------+
          |
          v
+--------------------+
|  Shipping /        |
|  Fulfillment       |
+--------------------+
```

Notes:

-   ReserveInventory and ReleaseInventory are idempotent, safe to call multiple times
-   CreateOrder snapshots products, prices, and attributes
-   ConfirmInventory is the point of no return: stock is permanently decremented
-   Backorder is optional per variant; if enabled, ReserveInventory can exceed actual stock

## Admin Panel

The admin panel will allows administrators to:

-   Create and manage products
-   Define product variants
-   Assign prices to variants
-   Manage catalog data
-   Manage inventory
-   Manage orders

Built using Inertia + React, the admin panel is tightly integrated with backend logic.

---

## API Layer

The API layer is designed to:

-   Serve external storefronts
-   Be independent from the admin panel
-   Expose only validated, normalized data
-   Support multi-currency pricing and inventory operations

---

## Current Status

✅ Project bootstrapped  
✅ Authentication (admin)  
✅ Catalog domain foundation  
✅ Products & variants schema  
✅ Pricing with multi-currency support  
✅ Inventory reservation and release actions  
✅ Order and OrderItem snapshots  
✅ CreateOrder action

🚧 In progress:

-   ConfirmInventory flow
-   Shipping, discounts, payments
-   Event-driven notifications
-   Frontend storefront integration

---

## Next Steps / Roadmap

This section outlines the remaining key features and improvements planned for the headless e-commerce backoffice:

### Orders & Inventory

-   **ConfirmInventory action**
    -   Finalizes stock decrement after successful payment
    -   Ensures atomic transactions
    -   Supports backorder logic per variant
-   **Inventory Events / Notifications**
    -   Trigger events when stock is low, reserved, released, or confirmed
    -   Optional webhook integration for external systems

### Checkout & Payments

-   **Payment gateway integration**
    -   Stripe, PayPal, or other providers
    -   Payment status updates tied to orders
    -   Refunds and cancellations handling
-   **Shipping & Fulfillment**
    -   Shipping rate calculation (fixed rates or carrier API)
    -   Order status updates upon shipment
    -   Support for multiple shipping addresses
-   **Discounts / Coupons / Promotions**
    -   Percentage or fixed discounts
    -   Rules-based applicability (product, category, order total)
    -   Expiry dates and usage limits

### Multi-Currency & Localization

-   Frontend selection of currency
-   Backend currency management
-   Price conversion, formatting, and rounding rules

### Frontend Storefront Integration

-   REST API endpoints versioned and documented
-   API responses normalized and filtered for frontend consumption
-   Potential GraphQL endpoint in future

### Developer Experience

-   Improve domain actions with interfaces and tests
-   Seeders and factories for easy local development
-   Documentation and example requests for API consumers

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

-   opinionated where needed

-   flexible where it matters

-   designed for developers building real-world e-commerce solutions

## License

This project is open-source and licensed under the MIT license.
