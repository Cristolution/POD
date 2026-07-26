# Product Requirements Document (PRD)
## Print-on-Demand (POD) Platform — Database & Core Domain Layer
**Version:** 1.0
**Scope of this document:** the data layer underpinning the platform (schema-driving decisions), not UI/UX.

---

## 1. Purpose & Vision

Build a designer-centric Print-on-Demand marketplace, conceptually similar to Printify (catalog + fulfillment routing) combined with Fiverr (negotiated custom work). A Designer uploads a design, offers it across one or more physical Materials (mugs, shirts, posters, etc.), and the platform routes fulfillment to a Print Service Provider (PSP) the Designer has a working relationship with. The Customer experiences a single storefront and a single checkout, unaware of (or only lightly exposed to) the backend routing complexity.

The platform takes a **fixed platform fee** (e.g. 5%) on top of the Designer's own price. The Designer sets the final customer-facing price for their work; the platform fee is additive, not a cut of the Designer's earnings.

---

## 2. Actors

| Actor | Description |
|---|---|
| **Customer** | Buys designs applied to materials. Can also open a Custom Order Request. |
| **Designer** | Uploads designs, defines pricing, assigns materials, maintains relationships with one or more Print Providers. |
| **Print Service Provider (PSP)** | A registered business (not a machine) that physically produces goods. Declares which materials/print methods it supports. Contracts with Designers under individually negotiated commission terms. |
| **Admin / Platform Staff** | Manages roles, permissions, and oversees provider onboarding (out of the box via RBAC tables). |

---

## 3. Core Product Decisions (locked, v1)

These were explicitly decided across design discussion and directly shape the schema:

1. **Design uploads are simple in v1**: one base file per design (image only). No AI-generated content, no auto-mockup generation. The file format field is extensible (future: SVG, AI source files, 3D/CNC formats) but only "image" is active in v1.
2. **One Design can be offered on multiple Materials** (e.g. a logo design offered on both a mug and a t-shirt) — this is core, not deferred.
3. **Design-to-Material pricing and provider routing is independent per material.** The same design can have different prices and different assigned PSPs depending on which material it's printed on.
4. **PSP capability is tracked separately from PSP assignment.** A PSP declares what it *can* do (capability); a Designer chooses which PSP handles a specific design+material combination (assignment). These are decoupled so assignments can be validated against real capabilities.
5. **Designer–PSP relationships are flexible, not exclusive.** A Designer can work with multiple PSPs and can reassign which PSP handles a given design+material combination over time (e.g., if a PSP has a long backlog).
6. **One Order = one Designer.** A Customer cannot mix designs from multiple Designers into a single order/cart. Multiple materials/designs from the *same* Designer are fine in one order.
7. **An Order can still involve multiple PSPs** (since different materials in the order may route to different providers). When this happens, **the order visibly splits into independent sub-orders**, one per PSP, each with its own status and shipment/tracking. This is customer-visible by design (per explicit decision), and each sub-order fails or succeeds independently — a delay or failure at one PSP does not block or affect the others.
8. **Customization ("this design needs a custom name") and special/bespoke orders are unified into one feature: the Custom Order Request.** This is a negotiated flow between Customer and Designer (Fiverr-offer-style), separate from the standard catalog checkout, and includes a real back-and-forth message thread.
9. **Custom Order Requests are permanent, standalone records.** They never convert into or merge with a standard Order — they carry their own lifecycle, pricing, and (for v1) simple carrier/tracking fields rather than the full multi-provider shipment model.
10. **Delivery/shipping companies are not modeled as platform entities in v1** — no registration, no profiles, no location-based dynamic shipping pricing. Carrier name and tracking number are stored as plain fields. This is explicitly flagged as a future area (dynamic, location-aware shipping pricing) that is out of scope for now.
11. **Refunds and cancellations are out of scope for v1.** If a sub-order fails, it fails independently; no refund workflow is modeled yet. A `payment_status` of "refunded" exists as a placeholder value only.
12. **Order-level status is derived, not stored.** The customer-facing "status" of an Order is computed from the statuses of its underlying sub-orders (e.g., partially shipped) rather than stored redundantly, to avoid data drift between parent and children.
13. **Historical price integrity**: prices are snapshotted onto order items at the time of purchase, so later changes to a Designer's listed price never rewrite historical order/commission data.
14. **All status/category fields use lookup tables, not enums**, so invalid states are rejected at the database level via foreign key constraints, and new statuses can be added without a schema migration.
15. **Authentication and authorization use a central `user` table plus standard RBAC** (`role`, `permission`, `role_permission`, `user_role`), with `customer`, `designer`, and `print_provider` each holding a 1:1 business-profile relationship to a `user` record. Admin UI conveniences (saved filters, per-user column preferences) are modeled generically so any future list view can reuse them.

---

## 4. Out of Scope (v1)

- AI-generated designs or automatic mockup generation
- Multiple file types per design (SVG, AI source, 3D/CNC formats) — schema is extensible, feature is not built
- Multi-designer shopping cart
- Refunds/cancellations workflow
- Registered delivery-company entities and location-based dynamic shipping pricing
- Merging Custom Order Requests into standard Orders
- Multi-shipment (partial/split box) shipments per sub-order

---

## 5. Success Criteria

- A Designer can list one design across multiple materials with independent pricing and routing.
- A Customer can check out with items from one Designer spanning multiple PSPs and see clearly separated shipment tracking per sub-order.
- A Customer can open a negotiated Custom Order Request, exchange messages with a Designer, agree on price/details, and have that tracked to delivery — entirely separate from the standard order pipeline.
- Commission math (Designer payout vs. platform's 5%-style fee) is always reconstructable from historical order data, even after a Designer changes their prices.
- No invalid status values can ever be written to the database (enforced via FK to lookup tables, not app-level string validation alone).

---

## 6. Key Assumptions

- Platform fee is a fixed/simple additive percentage in v1 (no tiered or negotiated platform fee).
- A PSP that lacks an active `designer_provider_agreement` with a Designer cannot be assigned to that Designer's `design_material` rows (enforced at application layer).
- A Designer cannot assign a PSP to a material the PSP hasn't declared capability for (enforced at application layer, validated against `printer_capability`).
