# Catalog Domain

The **Catalog** domain is responsible for describing products and exposing their
editorial and navigational information.

It is a **read-only, descriptive domain** whose sole purpose is to answer the question:

> _“What is this product?”_

It intentionally does **not** handle pricing, availability, or purchase logic.

---

## Responsibilities

The Catalog domain owns:

-   Product identity
-   Editorial content
-   Publication state
-   Navigability (slug-based access)
-   Semantic consistency of products

### Typical use cases

-   Product listing for storefronts
-   Product detail pages
-   SEO-friendly product URLs
-   Admin content management

---

## What the Catalog does **not** do

By design, the Catalog domain **does not**:

-   Handle prices or currencies
-   Know about inventory or stock availability
-   Interact with carts or orders
-   Handle payments or checkout logic
-   Decide whether a product can be purchased

Those responsibilities belong to other domains:

| Concern      | Domain    |
| ------------ | --------- |
| Pricing      | Pricing   |
| Availability | Inventory |
| Cart         | Cart      |
| Orders       | Orders    |
| Payments     | Payments  |

This separation is intentional and enforced to keep domain boundaries clear and maintainable.

---

## Core Models

### Product

A `Product` represents a **conceptual sellable item**, not a purchasable unit.

Typical examples:

-   “T-Shirt”
-   “Insulated Panel”
-   “Wireless Headphones”

A product:

-   Can exist without prices
-   Can exist without inventory
-   Can exist without being published

Purchasability is determined elsewhere.

---

## Product Lifecycle

Products follow a simple editorial lifecycle:

```
draft → published → archived
```

-   **draft**: visible only to administrators
-   **published**: visible to storefronts
-   **archived**: hidden from storefronts but preserved for historical integrity

The Catalog domain does not infer availability from other domains.

---

## API Philosophy

Catalog APIs are:

-   Read-only
-   Side-effect free
-   Provider-agnostic
-   Storefront-oriented

They expose **stable contracts** via DTOs and transformers.

Catalog responses never include:

-   Prices
-   Stock levels
-   Discounts
-   Purchase flags (e.g. `is_buyable`)

If storefronts need aggregated data, this must be handled by:

-   A BFF (Backend for Frontend), or
-   A dedicated aggregation layer

---

## DTOs & Transformers

The Catalog domain exposes data through explicit DTOs and transformers.

These are **domain contracts**, not view models.

If a field does not belong to the Catalog domain, it must not appear in its DTOs.

This ensures:

-   Clear ownership
-   Predictable APIs
-   Easier long-term evolution

---

## Why this separation matters

This strict boundary allows:

-   Independent evolution of pricing, inventory and checkout logic
-   Safer refactors over time
-   Multiple storefront implementations
-   Easier onboarding for contributors
-   Clear mental models for the codebase

The Catalog domain remains stable even as commerce rules evolve.

---

## Contribution Guidelines

When contributing to the Catalog domain:

-   Do not introduce pricing or availability concepts
-   Avoid cross-domain queries inside Catalog actions
-   Prefer explicit DTOs over ad-hoc arrays
-   Keep the domain read-only
-   Respect existing boundaries

If a feature feels “almost right” for Catalog, it probably belongs elsewhere.

---

## Related Domains

-   `Pricing` – prices, currencies, discounts
-   `Inventory` – stock, reservations, availability
-   `Cart` – cart state and mutations
-   `Orders` – order lifecycle and persistence
-   `Payments` – payment intents, webhooks, reconciliation

---

## Final note

The Catalog domain is the **semantic foundation** of the e-commerce system.

It is intentionally boring, predictable, and stable.

That is its strength.
