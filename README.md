<p align="center">
    <img src="https://raw.githubusercontent.com/imperfectcircle/yuki_headless_ecommerce/refs/heads/main/public/images/yuki_logo.webp" width="300" alt="Yuki Headless Ecommerce Logo">
</p>

# Yuki Headless E-commerce Backoffice (Laravel + Inertia + React)

Yuki is a **headless, backend-driven e-commerce backoffice** built with **Laravel 12**, **Inertia.js** and **React**.

Its goal is to provide a **scalable, reusable e-commerce engine** that can power multiple storefronts, while keeping a strong focus on **domain logic, data consistency and long-term maintainability**.

The storefront (B2C) is intentionally **decoupled** and can be implemented using any technology (Next.js, Nuxt, mobile apps, etc.), consuming the exposed APIs.

---

## Project Goals

-   Headless, API-first architecture
-   Backend-driven business logic
-   Clear domain separation
-   Reusable across multiple e-commerce projects
-   Easy to extend and maintain over time
-   Admin panel built with Inertia + React
-   Frontend-agnostic storefronts

---

## Tech Stack

### Backend

-   **Laravel 12**
-   MySQL / PostgreSQL
-   REST API (future-ready for GraphQL)

### Admin Panel

-   **Inertia.js**
-   **React**
-   Tailwind CSS

### Authentication

-   Laravel Breeze (admin only)

---

## Architecture Overview

This project follows a **domain-oriented, backend-driven architecture**.

-   The **admin panel** is the only frontend included in this repository
-   The **storefront is external** and consumes APIs
-   All business rules live in the backend domains

```
Backend (Laravel)
├── Domains
│ ├── Catalog
│ │ ├── Models (Product, ProductVariant, Attribute, AttributeOption)
│ │ ├── Actions (GenerateProductVariants, CreateProduct)
│ │ └── ...
│ ├── Pricing
│ │ ├── Models (Price, Currency)
│ │ ├── Actions (SetVariantPrice)
│ │ └── ...
│ ├── Inventory
│ │ ├── Models (Inventory)
│ │ ├── Actions
│ │ │ ├── ReserveInventory
| | | |-- ReleaseInventory
│ │ │ ├── ReserveOrderInventory
│ │ │ ├── ConfirmOrderInventory
│ │ │ └── ReleaseOrderInventory
│ │ └── ...
│ ├── Orders
│ │ ├── Models (Order, OrderItem)
│ │ ├── Actions (CreateOrder, MarkOrderAsPaid, MarkOrderAsFailed)
│ │ └── ...
│ ├── Payments
│ │ ├── Models (Payment)
│ │ ├── Actions (CreatePayment, HandleSuccessfulPayment, HandleFailedPayment)
│ │ └── ...
│ └── ...
├── Http
│ ├── Controllers (Admin / API / Webhooks)
│ └── Requests
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

-   A **Product** represents a conceptual item (e.g. _T-Shirt_)
-   A **ProductVariant** represents a sellable unit (e.g. _T-Shirt / Black / M_)
-   Every product **must have at least one variant**
-   Variants are the single source of truth for:
    -   Pricing
    -   Inventory
    -   SKU

---

### Pricing

-   Prices are stored as **integers** (minor units, e.g. cents)
-   Multi-currency is supported via the `Currency` model
-   Each variant can have multiple prices:
    -   Different currencies
    -   Validity ranges
    -   VAT rates
-   Prices are linked to variants via a polymorphic relation

---

### Inventory

-   Each `ProductVariant` has an associated inventory record
-   Inventory fields:
    -   `quantity`: total stock
    -   `reserved`: currently reserved stock
    -   `backorder_allowed`: optional, configurable per variant

#### Inventory Actions

-   `ReserveInventory`  
    Reserves stock for a single product variant, increasing the `reserved` quantity.
    It validates availability and backorder rules at variant level.

-   `ReserveOrderInventory`  
    Orchestrates inventory reservation for all order items.
    Ensures that either all items are successfully reserved or none are.

-   `ReleaseInventory`
    Releases previously reserved stock for a single product variant,
    decreasing the `reserved` quantity without affecting total stock.
    This action is safe to call multiple times and will never result in negative reservations.

-   `ConfirmOrderInventory`  
    Finalizes stock consumption after a successful payment.
    Decrements both `reserved` and `quantity` and transitions the order
    to its final paid state in a single atomic transaction.

-   `ReleaseOrderInventory`  
    Releases all reserved stock for an order after payment failure,
    cancellation, or automatic reservation timeout.

All inventory actions are **transactional and idempotent**.

---

### Orders & OrderItems

-   `Order` represents a customer purchase
-   `OrderItem` stores a **snapshot** of the variant at purchase time:
    -   SKU
    -   Name
    -   Attributes
    -   Unit price
    -   Quantity
    -   Totals

Orders store monetary and customer snapshots to guarantee historical consistency.

---

## Order Lifecycle

Orders follow a **strict, explicit lifecycle**:

```
draft
  ↓
reserved
  ↓
paid
```

### Possible transitions

-   `draft → reserved`

    -   Triggered by `ReserveOrderInventory`
    -   Stock is reserved but not consumed

-   `reserved → paid`

    -   Triggered after successful payment
    -   Inventory is confirmed

-   `reserved → cancelled`
    -   Triggered on payment failure or timeout
    -   Inventory is released

There are **no implicit transitions**.

---

## Payment Flow (Domain-Pure)

The domain **does not depend on payment providers**.

External systems (Stripe, PayPal, etc.) are handled at the infrastructure level (webhooks, APIs), then mapped to domain actions.

```
Payment Provider
        ↓
Webhook / API Controller
        ↓
HandleSuccessfulPayment | HandleFailedPayment
        ↓
Order + Inventory domain actions
```

This ensures:

-   Provider-agnostic domain logic
-   Idempotent payment handling
-   Easy extension to new gateways

---

## Admin Panel

Administrators can:

-   Manage products and variants
-   Define prices and currencies
-   Manage inventory and backorders
-   View and manage orders

The admin UI is built with **Inertia + React**, tightly integrated with backend logic.

---

## API Layer

The API layer is designed to:

-   Serve external storefronts
-   Be independent from the admin panel
-   Expose normalized, validated data
-   Support multi-currency pricing and inventory operations

---

## Current Status

✅ Project bootstrapped
✅ Authentication (admin)
✅ Catalog domain foundation
✅ Products & variants
✅ Multi-currency pricing
✅ Inventory reservation & release
✅ Order lifecycle defined
✅ Payment handling (domain-pure)

🚧 In progress:

-   Shipping & fulfillment
-   Discounts & promotions
-   Event-driven notifications
-   Storefront integration

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

Yuki is **not** a ready-made shop.

It is a foundation:

-   opinionated where needed
-   flexible where it matters
-   designed for developers building real-world e-commerce systems

---

## License

This project is open-source and licensed under the MIT license.

```

```
