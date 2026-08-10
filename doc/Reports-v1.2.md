# Reports Catalog — Print-on-Demand (POD) Platform

**Version:** 1.2
**Companion to:** `PRD-POD-Platform-Database-v1.2.md`, `SRS-POD-Platform-Database-v1.2.md`, `PermissionMatrix-v1.2.md`
**Scope:** every report the platform can produce, grouped by audience, with query shape, dimensions, refresh cadence, and source tables.

---

## 1. Why This Document

The v1.2 schema is designed to support reporting without schema changes — every status field is an inline enum, every price is snapshotted on `order_items.unit_price`, every media/notification is polymorphic, and every soft-delete is filterable. This document enumerates the **reports each role** can request, with enough query-shape detail that an implementation team can build them directly against the Eloquent models.

The reports are grouped by **audience**: Admin (platform-wide), Designer (own-design performance), Printer (own-fulfillment performance), and Customer (own-purchase history). A short final section covers **operational** and **system** reports that don't fit a single role.

---

## 2. Report Conventions

### 2.1 Time Zones

All reports run in the **platform's configured timezone** (`config('app.timezone')`). Dates are aggregated as `DATE(created_at)` in that timezone. The platform does NOT store per-user timezone in v1.2 — every user sees the same calendar day.

### 2.2 Currency

`unit_price` and `total_amount` are stored as `decimal(10,2)` in the platform's base currency (single-currency MVP). Multi-currency is a future extension.

### 2.3 Soft-Delete Filtering

Every report **excludes soft-deleted rows by default** unless explicitly requested via the `include_deleted` flag. This matches the application's default scope (`SoftDeletes` trait).

### 2.4 Date Range

Every report accepts `from` and `to` query parameters (ISO 8601 dates). Defaults are: most recent 30 days for operational reports, calendar year for financial reports.

### 2.5 Permissions

Each report has an explicit audience — it surfaces in the dashboard for that role only. Cross-tenant queries are blocked at the policy layer per `PermissionMatrix-v1.2.md`.

### 2.6 Refresh Cadence

| Cadence       | Use case                                                                       |
| ------------- | ------------------------------------------------------------------------------ |
| **Real-time** | Operational reports (cart counts, pending payments) — read directly on request |
| **Hourly**    | Designer and printer dashboards — aggregated every hour                        |
| **Daily**     | Financial reports — aggregated overnight                                       |
| **On-demand** | Customer purchase history — read live                                          |

For v1.2, **all reports are computed on read** from the source tables. There is no data warehouse / reporting replica in v1.2.

### 2.7 Common Dimensions

Most reports slice by these dimensions:

- **Time** — day, week, month, quarter, year
- **Geography** — `shipping_country` from `orders`
- **Category** — `categories` tree (resolved via `designs.category_id`)
- **Tag** — flattened from `design_tag`
- **Status** — `orders.status`, `order_items.status`, `payments.status`, `shipments.status`
- **Channel** — `orders` `created_at` source (web vs. mobile vs. admin-on-behalf) — informational only

### 2.8 Common Metrics

Every monetary report rolls up `order_items.unit_price * order_items.quantity` (the snapshotted revenue). The platform's fee and designer/printer payouts are **derived** from that snapshotted value at the application layer (PRD Decision 20).

---

## 3. Admin Reports (Platform-Wide)

These reports answer "How is the platform doing?" They are accessible only to `role='admin'`. Every admin report lives behind the `/api/admin/reports/...` route group.

### 3.1 Platform Overview Dashboard

**Question:** What is the platform's current state?

**Source tables:** `users`, `orders`, `order_items`, `payments`, `designs`, `product_templates`, `shipments`, `cart_items`

**Metrics (live, single number each):**

| Metric                             | Query                                                                                                    |
| ---------------------------------- | -------------------------------------------------------------------------------------------------------- |
| Total customers                    | `users.where('role', 'customer').whereNull('deleted_at').count()`                                        |
| Total designers                    | `users.where('role', 'designer').whereNull('deleted_at').count()`                                        |
| Total printers                     | `users.where('role', 'printer_provider').whereNull('deleted_at').count()`                                |
| Total published designs            | `designs.where('status', 'published').whereNull('deleted_at').count()`                                   |
| Total active product templates     | `product_templates.whereNull('deleted_at').count()`                                                      |
| Orders today                       | `orders.whereDate('created_at', today()).count()`                                                        |
| Orders this month                  | `orders.whereMonth('created_at', this_month).count()`                                                    |
| Revenue today (confirmed payments) | `payments.where('status', 'confirmed').whereDate('confirmed_at', today()).sum('order.total_amount')`     |
| Revenue this month                 | `payments.where('status', 'confirmed').whereMonth('confirmed_at', this_month).sum('order.total_amount')` |
| Pending payments                   | `payments.where('status', 'pending').count()`                                                            |
| Pending order items                | `order_items.where('status', 'pending').whereNull('deleted_at').count()`                                 |
| In-flight shipments                | `shipments.whereIn('status', ['pending', 'shipped']).count()`                                            |
| Abandoned carts (last 24h)         | `cart_items.where('updated_at', '>=', now()->subDay())->distinct('user_id').count('user_id')`            |

**Refresh:** Real-time, but the dashboard caches for 60 seconds.

### 3.2 Revenue by Day

**Question:** What is the daily revenue trend?

**Source tables:** `orders`, `order_items`, `payments`

**Query shape:**

```sql
SELECT DATE(orders.created_at) AS day,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS gross_revenue,
       SUM(order_items.quantity) AS units_sold
FROM orders
JOIN order_items ON order_items.order_id = orders.id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND orders.created_at BETWEEN :from AND :to
  AND orders.deleted_at IS NULL
GROUP BY day
ORDER BY day;
```

**Filters:** date range, payment method, shipping country, category.

**Refresh:** Daily.

### 3.3 Revenue by Category

**Question:** Which product categories drive the most revenue?

**Source tables:** `orders`, `order_items`, `design_product_mappings`, `designs`, `categories`

**Query shape:**

```sql
SELECT categories.id AS category_id,
       categories.name AS category_name,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS revenue
FROM orders
JOIN order_items ON order_items.order_id = orders.id
JOIN design_product_mappings dpm ON dpm.id = order_items.design_product_mapping_id
JOIN designs ON designs.id = dpm.design_id
JOIN categories ON categories.id = designs.category_id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND orders.created_at BETWEEN :from AND :to
  AND orders.deleted_at IS NULL
GROUP BY categories.id
ORDER BY revenue DESC;
```

**Refresh:** Daily.

### 3.4 Revenue by Designer

**Question:** Which designers earn the platform the most revenue?

**Source tables:** `orders`, `order_items`, `design_product_mappings`, `designs`, `designer_profiles`, `users`

**Query shape:**

```sql
SELECT users.id AS designer_id,
       users.name AS designer_name,
       designer_profiles.id AS designer_profile_id,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS gross_revenue,
       SUM(order_items.quantity) AS units_sold
FROM orders
JOIN order_items ON order_items.order_id = orders.id
JOIN design_product_mappings dpm ON dpm.id = order_items.design_product_mapping_id
JOIN designs ON designs.id = dpm.design_id
JOIN designer_profiles ON designer_profiles.id = designs.designer_id
JOIN users ON users.id = designer_profiles.user_id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND orders.created_at BETWEEN :from AND :to
  AND orders.deleted_at IS NULL
GROUP BY users.id, designer_profiles.id
ORDER BY gross_revenue DESC
LIMIT 25;
```

**Refresh:** Daily.

### 3.5 Revenue by Printer

**Question:** Which printers fulfill the most revenue?

**Source tables:** `orders`, `order_items`, `printer_provider_profiles`, `users`

**Query shape:**

```sql
SELECT users.id AS printer_id,
       users.name AS printer_name,
       printer_provider_profiles.id AS printer_profile_id,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS gross_revenue,
       SUM(order_items.quantity) AS units_sold
FROM orders
JOIN order_items ON order_items.order_id = orders.id
JOIN printer_provider_profiles ON printer_provider_profiles.id = order_items.printer_provider_id
JOIN users ON users.id = printer_provider_profiles.user_id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND orders.created_at BETWEEN :from AND :to
  AND orders.deleted_at IS NULL
GROUP BY users.id, printer_provider_profiles.id
ORDER BY gross_revenue DESC
LIMIT 25;
```

**Refresh:** Daily.

**Note** — the gross revenue here is the **customer-facing price** paid for items fulfilled by that printer. The printer's payout is derived from this at the application layer using the platform's commission rate.

### 3.6 Conversion Funnel (Browse → Cart → Order)

**Question:** Where do users drop off?

**Source tables:** `designs` (views — tracked via a future `design_views` table or `media` access logs), `cart_items`, `orders`, `order_items`

**Note** — v1.2 does **not** have a `design_views` table. The funnel is approximated from:

- Designs "considered" — count of `cart_items.distinct('design_product_mapping_id')` in the period
- Carts — `cart_items.count()` rows in the period
- Orders — `orders.count()` in the period

A full clickstream funnel is a future extension (Decision Out of Scope per PRD). The partial funnel is still useful.

**Refresh:** Daily.

### 3.7 Order Status Distribution

**Question:** Where are orders in the pipeline?

**Source tables:** `orders`

**Query shape:**

```sql
SELECT status, COUNT(*) AS count
FROM orders
WHERE created_at BETWEEN :from AND :to
  AND deleted_at IS NULL
GROUP BY status;
```

**Refresh:** Real-time (operational).

### 3.8 Order Item Status Distribution

**Question:** Where are individual items in the fulfillment pipeline?

**Source tables:** `order_items`

**Query shape:**

```sql
SELECT status, COUNT(*) AS count
FROM order_items
WHERE created_at BETWEEN :from AND :to
  AND deleted_at IS NULL
GROUP BY status;
```

**Refresh:** Real-time.

### 3.9 Payment Method Distribution

**Question:** Which payment methods do customers prefer?

**Source tables:** `payments`

**Query shape:**

```sql
SELECT method, status, COUNT(*) AS count,
       SUM(order.total_amount) AS total_value
FROM payments
JOIN orders AS order ON order.id = payments.order_id
WHERE payments.created_at BETWEEN :from AND :to
GROUP BY method, status;
```

**Refresh:** Daily.

### 3.10 Shipping Country Distribution

**Question:** Where are customers buying from?

**Source tables:** `orders`

**Query shape:**

```sql
SELECT shipping_country, COUNT(*) AS order_count,
       SUM(total_amount) AS revenue
FROM orders
WHERE created_at BETWEEN :from AND :to
  AND deleted_at IS NULL
GROUP BY shipping_country
ORDER BY order_count DESC;
```

**Refresh:** Daily.

### 3.11 Average Order Value (AOV)

**Question:** What is the typical order size?

**Source tables:** `orders`, `payments`

**Query shape:**

```sql
SELECT DATE(orders.created_at) AS day,
       AVG(orders.total_amount) AS aov,
       COUNT(*) AS order_count
FROM orders
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND orders.created_at BETWEEN :from AND :to
  AND orders.deleted_at IS NULL
GROUP BY day;
```

**Refresh:** Daily.

### 3.12 Top Designs by Revenue

**Question:** Which designs drive the most revenue?

**Source tables:** `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`

**Query shape:**

```sql
SELECT designs.id AS design_id,
       designs.title,
       designer_profiles.id AS designer_id,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS revenue,
       SUM(order_items.quantity) AS units_sold
FROM designs
JOIN design_product_mappings dpm ON dpm.design_id = designs.id
JOIN order_items ON order_items.design_product_mapping_id = dpm.id
JOIN orders ON orders.id = order_items.order_id
JOIN payments ON payments.order_id = orders.id
JOIN designer_profiles ON designer_profiles.id = designs.designer_id
WHERE payments.status = 'confirmed'
  AND orders.created_at BETWEEN :from AND :to
  AND designs.deleted_at IS NULL
GROUP BY designs.id, designer_profiles.id
ORDER BY revenue DESC
LIMIT 25;
```

**Refresh:** Daily.

### 3.13 Top Product Templates by Revenue

**Question:** Which product templates drive the most revenue?

**Source tables:** `product_templates`, `product_variants`, `design_product_mappings`, `order_items`, `orders`, `payments`, `printer_provider_profiles`

**Query shape:**

```sql
SELECT product_templates.id AS template_id,
       product_templates.name,
       product_templates.type,
       printer_provider_profiles.id AS printer_id,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS revenue,
       SUM(order_items.quantity) AS units_sold
FROM product_templates
JOIN design_product_mappings dpm ON dpm.product_template_id = product_templates.id
JOIN order_items ON order_items.design_product_mapping_id = dpm.id
JOIN orders ON orders.id = order_items.order_id
JOIN payments ON payments.order_id = orders.id
JOIN printer_provider_profiles ON printer_provider_profiles.id = product_templates.printer_provider_id
WHERE payments.status = 'confirmed'
  AND orders.created_at BETWEEN :from AND :to
  AND product_templates.deleted_at IS NULL
GROUP BY product_templates.id, printer_provider_profiles.id
ORDER BY revenue DESC
LIMIT 25;
```

**Refresh:** Daily.

### 3.14 Most-Used Tags

**Question:** Which tags correlate with sales?

**Source tables:** `design_tag`, `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`

**Query shape:**

```sql
SELECT tags.id AS tag_id,
       tags.name,
       COUNT(DISTINCT designs.id) AS design_count,
       COUNT(DISTINCT order_items.id) AS times_ordered,
       SUM(order_items.unit_price * order_items.quantity) AS revenue
FROM tags
JOIN design_tag ON design_tag.tag_id = tags.id
JOIN designs ON designs.id = design_tag.design_id
LEFT JOIN design_product_mappings dpm ON dpm.design_id = designs.id
LEFT JOIN order_items ON order_items.design_product_mapping_id = dpm.id
LEFT JOIN orders ON orders.id = order_items.order_id
LEFT JOIN payments ON payments.order_id = orders.id
  AND payments.status = 'confirmed'
  AND orders.created_at BETWEEN :from AND :to
GROUP BY tags.id
ORDER BY times_ordered DESC
LIMIT 25;
```

**Refresh:** Daily.

### 3.15 Customer Cohort Analysis

**Question:** How do customer cohorts retain over time?

**Source tables:** `users`, `orders`

**Query shape:**

```sql
SELECT DATE_FORMAT(users.created_at, '%Y-%m') AS cohort_month,
       DATEDIFF(orders.created_at, users.created_at) / 30 AS age_months,
       COUNT(DISTINCT users.id) AS cohort_size,
       COUNT(DISTINCT orders.id) AS order_count
FROM users
JOIN orders ON orders.customer_id = users.id
WHERE users.role = 'customer'
  AND users.deleted_at IS NULL
GROUP BY cohort_month, age_months;
```

**Refresh:** Daily.

### 3.16 Customer Lifetime Value (CLV)

**Question:** Which customers spend the most over time?

**Source tables:** `users`, `orders`, `payments`

**Query shape:**

```sql
SELECT users.id,
       users.name,
       users.email,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(orders.total_amount) AS ltv,
       MAX(orders.created_at) AS last_order_at
FROM users
JOIN orders ON orders.customer_id = users.id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND users.role = 'customer'
  AND users.deleted_at IS NULL
GROUP BY users.id
ORDER BY ltv DESC
LIMIT 100;
```

**Refresh:** Daily.

### 3.17 Refund / Cancellation Rate

**Question:** How often do orders get cancelled?

**Source tables:** `orders`, `order_items`

**Query shape:**

```sql
SELECT DATE(orders.created_at) AS day,
       COUNT(DISTINCT orders.id) AS total_orders,
       SUM(CASE WHEN orders.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
       SUM(CASE WHEN orders.status = 'cancelled' THEN 1 ELSE 0 END) * 1.0 / COUNT(*) AS cancellation_rate
FROM orders
WHERE orders.created_at BETWEEN :from AND :to
  AND orders.deleted_at IS NULL
GROUP BY day;
```

**Refresh:** Daily.

**Note** — v1.2 does not have a separate refunds workflow. `cancelled` is the only proxy. A real refunds table is a future extension.

### 3.18 Delivery Performance

**Question:** How long does delivery take on average?

**Source tables:** `shipments`, `orders`

**Query shape:**

```sql
SELECT delivery_companies.id,
       delivery_companies.name,
       COUNT(*) AS shipment_count,
       AVG(JULIANDAY(shipments.delivered_at) - JULIANDAY(shipments.shipped_at)) AS avg_delivery_days,
       SUM(CASE WHEN shipments.status = 'returned' THEN 1 ELSE 0 END) AS returned_count
FROM shipments
JOIN delivery_companies ON delivery_companies.id = shipments.delivery_company_id
WHERE shipments.delivered_at IS NOT NULL
GROUP BY delivery_companies.id;
```

**Refresh:** Daily.

### 3.19 Designer / Printer Onboarding Funnel

**Question:** Are new designers being approved? Are new printers being verified?

**Source tables:** `users`, `designer_profiles`, `printer_provider_profiles`

**Query shape:**

```sql
SELECT DATE(users.created_at) AS day,
       SUM(CASE WHEN users.role = 'designer' THEN 1 ELSE 0 END) AS new_designers,
       SUM(CASE WHEN users.role = 'printer_provider' THEN 1 ELSE 0 END) AS new_printers
FROM users
WHERE users.role IN ('designer', 'printer_provider')
  AND users.created_at BETWEEN :from AND :to
GROUP BY day;
```

**Note** — v1.2 has no approval workflow (described in PRD as deliberate simplification). Admins soft-delete accounts that don't meet standards. The funnel here just counts accounts created.

**Refresh:** Daily.

### 3.20 Deletion / Ban Audit

**Question:** Which accounts were soft-deleted in the period?

**Source tables:** `users`

**Query shape:**

```sql
SELECT id, name, email, role, deleted_at
FROM users
WHERE deleted_at IS NOT NULL
  AND deleted_at BETWEEN :from AND :to
ORDER BY deleted_at DESC;
```

**Refresh:** Real-time.

---

## 4. Designer Reports (Designer-Facing)

These reports are scoped to the **authenticated designer's own designs and mappings**. They are accessible only when `role='designer'` and the queried records belong to that designer.

### 4.1 Designer Dashboard

**Question:** How are my designs performing right now?

**Source tables:** `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`

**Metrics (live):**

| Metric                         | Query                                                                                                                                                    |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Total published designs        | `designs.where('designer_id', self).where('status', 'published').count()`                                                                                |
| Total draft designs            | `designs.where('designer_id', self).where('status', 'draft').count()`                                                                                    |
| Total mappings                 | `design_product_mappings.whereHas('design', fn => designer_id = self).count()`                                                                           |
| Orders this month              | `order_items.whereHas('designProductMapping.design', fn => designer_id = self).whereMonth('orders.created_at', this_month).distinct('order_id').count()` |
| Revenue this month (gross)     | `SUM(order_items.unit_price * order_items.quantity)` filtered                                                                                            |
| Units sold this month          | `SUM(order_items.quantity)` filtered                                                                                                                     |
| Best-selling design this month | `JOIN designs on designer_id = self, GROUP BY design, ORDER BY units_sold DESC LIMIT 1`                                                                  |

**Refresh:** Real-time.

### 4.2 Revenue by Design (Designer View)

**Question:** Which of my designs are performing best?

**Source tables:** `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`

**Query shape:**

```sql
SELECT designs.id AS design_id,
       designs.title,
       designs.status,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS revenue,
       SUM(order_items.quantity) AS units_sold
FROM designs
JOIN design_product_mappings dpm ON dpm.design_id = designs.id
JOIN order_items ON order_items.design_product_mapping_id = dpm.id
JOIN orders ON orders.id = order_items.order_id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND designs.designer_id = :designer_id
  AND orders.created_at BETWEEN :from AND :to
  AND designs.deleted_at IS NULL
GROUP BY designs.id
ORDER BY revenue DESC;
```

**Refresh:** Real-time.

### 4.3 Revenue by Mapping (Designer View)

**Question:** Which design-on-template combination is most profitable?

**Source tables:** `design_product_mappings`, `product_templates`, `order_items`, `orders`, `payments`

**Query shape:**

```sql
SELECT dpm.id AS mapping_id,
       designs.title AS design_title,
       product_templates.name AS template_name,
       dpm.final_price,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS revenue,
       SUM(order_items.quantity) AS units_sold
FROM design_product_mappings dpm
JOIN designs ON designs.id = dpm.design_id
JOIN product_templates ON product_templates.id = dpm.product_template_id
JOIN order_items ON order_items.design_product_mapping_id = dpm.id
JOIN orders ON orders.id = order_items.order_id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND designs.designer_id = :designer_id
  AND orders.created_at BETWEEN :from AND :to
GROUP BY dpm.id
ORDER BY revenue DESC;
```

**Refresh:** Real-time.

### 4.4 Mockup Performance (Designer View)

**Question:** Which of my mockups attracts the most attention?

**Source tables:** `media` (polymorphic; `model_type='Design'`, `collection_name='mockup'`), `designs`

**Note** — v1.2 does not track impression counts. The mockup "performance" report is limited to **which mockups exist** for each design. A future extension would track views.

**Refresh:** Real-time.

### 4.5 Tag Performance (Designer View)

**Question:** Are my tagged designs performing well?

**Source tables:** `design_tag`, `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`

**Query shape:** Same as Section 3.14, scoped to the designer's own designs.

**Refresh:** Real-time.

### 4.6 Designer Payout Statement

**Question:** How much should the platform pay me?

**Source tables:** `orders`, `order_items`, `design_product_mappings`, `designs`, `payments`, `settings` (for commission rate)

**Query shape:**

```sql
SELECT DATE(orders.created_at) AS day,
       SUM(order_items.unit_price * order_items.quantity) AS gross_revenue,
       SUM(order_items.unit_price * order_items.quantity) * (1 - :commission_rate) AS designer_payout,
       SUM(order_items.unit_price * order_items.quantity) * :commission_rate AS platform_fee
FROM orders
JOIN order_items ON order_items.order_id = orders.id
JOIN design_product_mappings dpm ON dpm.id = order_items.design_product_mapping_id
JOIN designs ON designs.id = dpm.design_id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND designs.designer_id = :designer_id
  AND orders.created_at BETWEEN :from AND :to
GROUP BY day;
```

**Refresh:** Daily.

**Note** — `commission_rate` is read from `settings` table key `platform.commission_rate` (admin-managed). v1.2 uses a single flat rate; per-category rates are a future extension.

### 4.7 Inventory Status (own mappings)

**Question:** Are any of my mappings on inactive templates?

**Source tables:** `design_product_mappings`, `product_templates`

**Query shape:**

```sql
SELECT dpm.id, dpm.final_price, designs.title, product_templates.name AS template_name, product_templates.deleted_at
FROM design_product_mappings dpm
JOIN designs ON designs.id = dpm.design_id
JOIN product_templates ON product_templates.id = dpm.product_template_id
WHERE designs.designer_id = :designer_id
  AND (product_templates.deleted_at IS NOT NULL);
```

This flags mappings that the designer can no longer fulfill through the original template.

**Refresh:** Real-time.

### 4.8 Draft vs. Published Conversion

**Question:** What percentage of my drafts actually get published?

**Source tables:** `designs`

**Query shape:**

```sql
SELECT
  SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS drafts,
  SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
  SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived,
  SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) * 1.0 / NULLIF(COUNT(*), 0) AS publish_rate
FROM designs
WHERE designer_id = :designer_id
  AND deleted_at IS NULL;
```

**Refresh:** Real-time.

---

## 5. Printer Reports (Printer-Facing)

These reports are scoped to the **authenticated printer's own templates and order_items**. They are accessible only when `role='printer_provider'` and the queried records belong to that printer.

### 5.1 Printer Dashboard

**Question:** What do I need to work on today?

**Source tables:** `order_items`, `orders`, `shipments`, `payments`

**Metrics (live):**

| Metric                             | Query                                                                               |
| ---------------------------------- | ----------------------------------------------------------------------------------- |
| New order items in `pending`       | `order_items.where('printer_provider_id', self).where('status', 'pending').count()` |
| Items in `received`                | `order_items.where('status', 'received').count()`                                   |
| Items in `printing`                | `order_items.where('status', 'printing').count()`                                   |
| Items in `printed` (ready to ship) | `order_items.where('status', 'printed').count()`                                    |
| Items in `handed_off`              | `order_items.where('status', 'handed_off').count()`                                 |
| Pending shipments                  | `shipments.where('printer_provider_id', self).where('status', 'pending').count()`   |
| In-transit shipments               | `shipments.where('status', 'shipped').count()`                                      |
| Delivered shipments today          | `shipments.where('status', 'delivered').whereDate('delivered_at', today()).count()` |
| Today's revenue (confirmed)        | `SUM(order_items.unit_price * order_items.quantity)` for today's confirmed orders   |
| Average turnaround time            | `AVG(delivered_at - handed_off_at)` for completed shipments                         |

**Refresh:** Real-time.

### 5.2 Order Items Needing Action

**Question:** Which order items are waiting for me to move to the next status?

**Source tables:** `order_items`, `orders`, `design_product_mappings`, `designs`, `product_templates`, `product_variants`

**Query shape:**

```sql
SELECT order_items.id, order_items.status, order_items.quantity, order_items.unit_price,
       orders.id AS order_id, orders.customer_id, orders.shipping_city, orders.shipping_country,
       designs.title AS design_title,
       product_templates.name AS template_name,
       product_variants.attributes AS variant_attributes
FROM order_items
JOIN orders ON orders.id = order_items.order_id
JOIN design_product_mappings dpm ON dpm.id = order_items.design_product_mapping_id
JOIN designs ON designs.id = dpm.design_id
JOIN product_templates ON product_templates.id = dpm.product_template_id
LEFT JOIN product_variants ON product_variants.id = order_items.product_variant_id
WHERE order_items.printer_provider_id = :printer_id
  AND order_items.status IN ('pending', 'received', 'printing', 'printed')
  AND orders.deleted_at IS NULL
  AND order_items.deleted_at IS NULL
ORDER BY orders.created_at ASC;
```

This is the **work queue** for the printer.

**Refresh:** Real-time.

### 5.3 Revenue by Product Template (Printer View)

**Question:** Which of my templates makes the most money?

**Source tables:** `product_templates`, `order_items`, `orders`, `payments`

**Query shape:**

```sql
SELECT product_templates.id AS template_id,
       product_templates.name,
       product_templates.type,
       COUNT(DISTINCT orders.id) AS order_count,
       SUM(order_items.unit_price * order_items.quantity) AS revenue,
       SUM(order_items.quantity) AS units_sold
FROM product_templates
JOIN order_items ON order_items.printer_provider_id = product_templates.printer_provider_id
JOIN design_product_mappings dpm ON dpm.id = order_items.design_product_mapping_id
  AND dpm.product_template_id = product_templates.id
JOIN orders ON orders.id = order_items.order_id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND product_templates.printer_provider_id = :printer_id
  AND orders.created_at BETWEEN :from AND :to
GROUP BY product_templates.id
ORDER BY revenue DESC;
```

**Refresh:** Daily.

### 5.4 Shipment Performance (Printer View)

**Question:** How is my shipping performance trending?

**Source tables:** `shipments`, `delivery_companies`

**Query shape:**

```sql
SELECT delivery_companies.id AS company_id,
       delivery_companies.name AS company_name,
       COUNT(*) AS shipment_count,
       AVG(JULIANDAY(shipments.delivered_at) - JULIANDAY(shipments.shipped_at)) AS avg_delivery_days,
       SUM(CASE WHEN shipments.status = 'returned' THEN 1 ELSE 0 END) AS returned_count
FROM shipments
JOIN delivery_companies ON delivery_companies.id = shipments.delivery_company_id
WHERE shipments.printer_provider_id = :printer_id
  AND shipments.delivered_at IS NOT NULL
GROUP BY delivery_companies.id;
```

**Refresh:** Daily.

### 5.5 Printer Payout Statement

**Question:** How much should the platform pay me?

**Source tables:** `order_items`, `orders`, `payments`, `settings`

**Query shape:**

```sql
SELECT DATE(orders.created_at) AS day,
       SUM(order_items.unit_price * order_items.quantity) AS gross_revenue,
       SUM(order_items.unit_price * order_items.quantity) * (1 - :commission_rate) AS printer_payout,
       SUM(order_items.unit_price * order_items.quantity) * :commission_rate AS platform_fee
FROM orders
JOIN order_items ON order_items.order_id = orders.id
JOIN payments ON payments.order_id = orders.id
WHERE payments.status = 'confirmed'
  AND order_items.printer_provider_id = :printer_id
  AND orders.created_at BETWEEN :from AND :to
GROUP BY day;
```

**Refresh:** Daily.

### 5.6 Status Transition Histogram (Printer View)

**Question:** How long does each status typically last?

**Source tables:** `order_items` (with timestamps captured via `notifications` or future audit log)

**Note** — v1.2 does **not** track per-status timestamps on `order_items`. The transition timeline is approximated through `orders.updated_at` vs `shipments.shipped_at/delivered_at`. A future audit log table would give precise data.

**Refresh:** Daily (approximate).

### 5.7 Templates Without Active Designs

**Question:** Which of my templates are not being used by any designer?

**Source tables:** `product_templates`, `design_product_mappings`

**Query shape:**

```sql
SELECT product_templates.id, product_templates.name, product_templates.created_at
FROM product_templates
LEFT JOIN design_product_mappings dpm ON dpm.product_template_id = product_templates.id
WHERE product_templates.printer_provider_id = :printer_id
  AND product_templates.deleted_at IS NULL
  AND dpm.id IS NULL;
```

**Refresh:** Real-time.

### 5.8 Inventory Status (Printer View)

**Question:** Which of my templates or variants are soft-deleted but still referenced?

**Source tables:** `product_templates`, `product_variants`, `order_items`

**Query shape:**

```sql
SELECT product_templates.id, product_templates.name, product_templates.deleted_at,
       COUNT(DISTINCT order_items.id) AS orphan_items
FROM product_templates
JOIN design_product_mappings dpm ON dpm.product_template_id = product_templates.id
JOIN order_items ON order_items.design_product_mapping_id = dpm.id
WHERE product_templates.printer_provider_id = :printer_id
  AND product_templates.deleted_at IS NOT NULL
GROUP BY product_templates.id;
```

This flags historical order items referencing soft-deleted templates — useful for the printer to know what bookkeeping remains.

**Refresh:** Real-time.

---

## 6. Customer Reports (Customer-Facing)

Limited to the authenticated customer's own purchase history. The customer never sees other customers' data.

### 6.1 Customer Order History

**Question:** What have I ordered?

**Source tables:** `orders`, `order_items`, `design_product_mappings`, `designs`, `product_templates`, `product_variants`, `shipments`, `payments`

**Query shape:**

```sql
SELECT orders.id AS order_id,
       orders.created_at,
       orders.status,
       orders.total_amount,
       payments.method AS payment_method,
       payments.status AS payment_status
FROM orders
LEFT JOIN payments ON payments.order_id = orders.id
WHERE orders.customer_id = :customer_id
  AND orders.deleted_at IS NULL
ORDER BY orders.created_at DESC;
```

**Refresh:** Real-time.

### 6.2 Order Detail (with derived status)

**Question:** What is the current state of my order?

**Query shape:** Joins `orders`, `order_items`, `payments`, `shipments`. The `derived_status` is computed in the `OrderResource` from the highest-level item status (see `Order::derivedStatus()`).

**Refresh:** Real-time.

### 6.3 Active Shipments

**Question:** Which of my orders are currently being delivered?

**Source tables:** `shipments`, `orders`, `delivery_companies`

**Query shape:**

```sql
SELECT shipments.id AS shipment_id,
       shipments.tracking_number,
       shipments.tracking_url,
       shipments.status,
       shipments.shipped_at,
       shipments.delivered_at,
       delivery_companies.name AS delivery_company,
       orders.id AS order_id
FROM shipments
JOIN orders ON orders.id = shipments.order_id
JOIN delivery_companies ON delivery_companies.id = shipments.delivery_company_id
WHERE orders.customer_id = :customer_id
  AND shipments.status IN ('pending', 'shipped')
ORDER BY shipments.shipped_at DESC;
```

**Refresh:** Real-time.

### 6.4 Cart Summary

**Question:** What's in my cart?

**Source tables:** `cart_items`, `design_product_mappings`, `designs`, `product_templates`, `product_variants`

**Note** — this is structurally the same as the cart items resource, not a heavy report. The "report" view shows the cart's total value (`SUM(unit_price * quantity)`) computed live.

**Refresh:** Real-time.

### 6.5 Customer Spending Summary

**Question:** How much have I spent this year?

**Source tables:** `orders`, `payments`

**Query shape:**

```sql
SELECT DATE_TRUNC('month', orders.created_at) AS month,
       COUNT(*) AS order_count,
       SUM(orders.total_amount) AS spent
FROM orders
JOIN payments ON payments.order_id = orders.id
WHERE orders.customer_id = :customer_id
  AND payments.status = 'confirmed'
  AND orders.deleted_at IS NULL
GROUP BY month
ORDER BY month DESC;
```

**Refresh:** Real-time.

### 6.6 Recommendations (Designs You've Bought)

**Question:** Based on my purchase history, what other designs might I like?

**Source tables:** `orders`, `order_items`, `design_product_mappings`, `designs`, `categories`, `design_tag`

**Note** — v1.2 has no machine-learning recommendation engine. A simple co-occurrence recommendation: find designs that share the same categories or tags as the customer's purchased designs, that the customer hasn't already bought.

**Query shape (simplified):**

```sql
SELECT DISTINCT designs.id, designs.title, COUNT(*) AS relevance
FROM designs
JOIN design_tag ON design_tag.design_id = designs.id
WHERE design_tag.tag_id IN (
    SELECT DISTINCT design_tag.tag_id
    FROM design_tag
    JOIN design_product_mappings dpm ON dpm.design_id = design_tag.design_id
    JOIN order_items ON order_items.design_product_mapping_id = dpm.id
    JOIN orders ON orders.id = order_items.order_id
    WHERE orders.customer_id = :customer_id
)
AND designs.id NOT IN (
    SELECT dpm.design_id
    FROM orders
    JOIN order_items ON order_items.order_id = orders.id
    JOIN design_product_mappings dpm ON dpm.id = order_items.design_product_mapping_id
    WHERE orders.customer_id = :customer_id
)
AND designs.status = 'published'
AND designs.deleted_at IS NULL
GROUP BY designs.id
ORDER BY relevance DESC
LIMIT 10;
```

**Refresh:** Daily.

---

## 7. Operational Reports (Admin-Ops)

These are platform-internal reports that don't tie to a single role's dashboard. They are read by admin-ops and customer support.

### 7.1 Failed / Stuck Shipments

**Question:** Which shipments have been pending or in-transit for too long?

**Source tables:** `shipments`, `orders`

**Query shape:**

```sql
SELECT shipments.id, shipments.status, shipments.shipped_at,
       orders.id AS order_id, orders.created_at AS order_date,
       JULIANDAY('now') - JULIANDAY(shipments.created_at) AS age_days
FROM shipments
JOIN orders ON orders.id = shipments.order_id
WHERE shipments.status IN ('pending', 'shipped')
  AND (shipments.shipped_at IS NULL OR shipments.shipped_at < DATE('now', '-7 days'))
ORDER BY age_days DESC;
```

**Refresh:** Hourly.

### 7.2 Stuck Payments

**Question:** Which payments have been `pending` for more than X days?

**Source tables:** `payments`, `orders`

**Query shape:**

```sql
SELECT payments.id, payments.method, payments.created_at,
       orders.id AS order_id, orders.customer_id,
       JULIANDAY('now') - JULIANDAY(payments.created_at) AS age_days
FROM payments
JOIN orders ON orders.id = payments.order_id
WHERE payments.status = 'pending'
  AND payments.created_at < DATE('now', '-3 days')
ORDER BY age_days DESC;
```

**Refresh:** Hourly.

### 7.3 Order Items Awaiting Printer

**Question:** Which order items are in `pending` for too long?

**Source tables:** `order_items`

**Query shape:**

```sql
SELECT id, order_id, printer_provider_id, status, created_at,
       JULIANDAY('now') - JULIANDAY(created_at) AS age_days
FROM order_items
WHERE status = 'pending'
  AND created_at < DATE('now', '-2 days')
ORDER BY age_days DESC;
```

**Refresh:** Hourly.

### 7.4 Cart Abandonment

**Question:** Which customers added to cart but never checked out?

**Source tables:** `cart_items`, `orders`

**Query shape:**

```sql
SELECT users.id, users.name, users.email,
       COUNT(DISTINCT cart_items.id) AS cart_items_count,
       MAX(cart_items.updated_at) AS last_cart_activity
FROM users
JOIN cart_items ON cart_items.user_id = users.id
LEFT JOIN orders ON orders.customer_id = users.id
  AND orders.created_at > cart_items.updated_at
WHERE users.role = 'customer'
  AND users.deleted_at IS NULL
  AND orders.id IS NULL
GROUP BY users.id
HAVING last_cart_activity < DATE('now', '-7 days')
ORDER BY last_cart_activity DESC;
```

**Refresh:** Daily.

### 7.5 Unread Notifications

**Question:** Which users have unread notifications?

**Source tables:** `notifications` (polymorphic)

**Query shape:**

```sql
SELECT notifiable_type, notifiable_id, COUNT(*) AS unread_count
FROM notifications
WHERE read_at IS NULL
GROUP BY notifiable_type, notifiable_id
ORDER BY unread_count DESC;
```

**Refresh:** Real-time.

### 7.6 Media Storage Usage

**Question:** How much disk are media files taking?

**Source tables:** `media`

**Note** — v1.2 does not store `file_size` on the `media` table. This report is approximated by file-system scan (outside the DB). A future schema change would add `media.size_bytes` for in-DB reporting.

**Refresh:** Daily.

### 7.7 Soft-Delete Audit (all entities)

**Question:** What was soft-deleted in the period?

**Source tables:** all soft-deletable tables

**Query shape:** A union over `users`, `designs`, `product_templates`, `product_variants`, `design_product_mappings`, `orders`, `order_items`, `payments`, joined on `deleted_at BETWEEN :from AND :to`.

**Refresh:** Real-time.

### 7.8 Designer Aggregation Health

**Question:** Are any designers without a profile, or vice versa?

**Source tables:** `users`, `designer_profiles`, `printer_provider_profiles`

**Query shape:**

```sql
SELECT id, name, email, role
FROM users
WHERE role = 'designer'
  AND id NOT IN (SELECT user_id FROM designer_profiles)
UNION
SELECT id, name, email, role
FROM users
WHERE role = 'printer_provider'
  AND id NOT IN (SELECT user_id FROM printer_provider_profiles);
```

**Refresh:** Daily.

### 7.9 Orphaned Media

**Question:** Are there any media rows pointing to non-existent owners?

**Source tables:** `media` (polymorphic)

**Query shape:** For each row, attempt to resolve the `(model_type, model_id)` pair against the appropriate table. Report any where the parent is missing.

**Refresh:** Daily.

### 7.10 Order Items Without Shipment

**Question:** Which `handed_off` items have no shipment row?

**Source tables:** `order_items`, `shipments`

**Query shape:**

```sql
SELECT order_items.id, order_items.order_id, order_items.printer_provider_id,
       order_items.status
FROM order_items
LEFT JOIN shipments ON shipments.order_id = order_items.order_id
  AND shipments.printer_provider_id = order_items.printer_provider_id
WHERE order_items.status = 'handed_off'
  AND shipments.id IS NULL;
```

**Refresh:** Hourly.

### 7.11 Cart Items Stuck on Inactive Variants

**Question:** Are there cart items pointing to soft-deleted mappings or inactive variants?

**Source tables:** `cart_items`, `design_product_mappings`, `product_variants`

**Query shape:**

```sql
SELECT cart_items.id, cart_items.user_id, cart_items.product_variant_id,
       product_variants.is_active, product_variants.deleted_at
FROM cart_items
JOIN product_variants ON product_variants.id = cart_items.product_variant_id
WHERE product_variants.deleted_at IS NOT NULL
   OR product_variants.is_active = 0;
```

**Refresh:** Daily.

### 7.12 Designer Deleted With Mappings

**Question:** Which designers have mappings referencing soft-deleted designs?

**Source tables:** `design_product_mappings`, `designs`

**Query shape:**

```sql
SELECT dpm.id, designs.designer_id, designs.deleted_at
FROM design_product_mappings dpm
JOIN designs ON designs.id = dpm.design_id
WHERE designs.deleted_at IS NOT NULL;
```

**Refresh:** Daily.

---

## 8. System / Performance Reports

These are introspection reports used by the platform team to monitor the database itself.

### 8.1 Table Sizes

**Question:** How big is each table?

**Source tables:** all tables

**Query shape:** `SELECT name, SUM(pgsql_relation_size(...))` — varies by DB engine. For SQLite: `SELECT name FROM sqlite_master WHERE type='table'`.

**Refresh:** Daily.

### 8.2 Index Hit Rate

**Question:** Are indexes being used?

**Source:** Database engine metrics (e.g. `pg_stat_user_indexes` for Postgres, `sys.schema_index_usage_stats` for MySQL, `EXPLAIN QUERY PLAN` for SQLite).

**Refresh:** Daily.

### 8.3 Slow Query Log

**Question:** Which queries exceed the time threshold?

**Source:** Application log (Laravel's `DB::listen` or `Pail`).

**Refresh:** Real-time.

### 8.4 Soft-Delete Ratio

**Question:** Are soft-deletes accumulating?

**Source tables:** soft-deletable tables

**Query shape:**

```sql
SELECT
  (SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL) * 1.0 /
  NULLIF((SELECT COUNT(*) FROM users), 0) AS user_delete_ratio,
  (SELECT COUNT(*) FROM designs WHERE deleted_at IS NOT NULL) * 1.0 /
  NULLIF((SELECT COUNT(*) FROM designs), 0) AS design_delete_ratio,
  (SELECT COUNT(*) FROM orders WHERE deleted_at IS NOT NULL) * 1.0 /
  NULLIF((SELECT COUNT(*) FROM orders), 0) AS order_delete_ratio;
```

**Refresh:** Weekly.

---

## 9. Reports That Are NOT in v1.2

Per the PRD's "Out of Scope" section, these reports are explicitly **not** supported:

| Future report                                     | Why not in v1.2                                               |
| ------------------------------------------------- | ------------------------------------------------------------- |
| Refund accounting                                 | No refund table; `cancelled` is the only proxy                |
| Customer / designer / printer messaging analytics | No messaging in v1.2                                          |
| Co-cart / wishlist analytics                      | No wishlist in v1.2                                           |
| AI-generated mockup performance                   | No AI in v1.2                                                 |
| Custom-order pricing analysis                     | No custom-order requests in v1.2                              |
| Real-time clickstream / impression analytics      | No `design_views` table; clickstream not modeled              |
| Multi-shipment-per-batch shipping analytics       | One shipment per (order, printer) in v1.2                     |
| Per-status SLA escalations                        | Per-status timestamps not tracked on `order_items`            |
| Designer-designer or printer-printer comparisons  | Not a v1.2 use case                                           |
| Tax / VAT breakdown                               | Single-currency, no tax tables in v1.2                        |
| Inventory forecasting                             | No raw inventory / stock tables; variants abstract away stock |

These would each require a schema change.

---

## 10. Implementation Notes

### 10.1 Query Patterns

- **Every monetary report** joins `payments` to filter on `payments.status = 'confirmed'`. Revenue is reported against confirmed payments only.
- **Every count** excludes `deleted_at IS NOT NULL` rows on soft-deletable tables.
- **Every report** filters dates via `payments.confirmed_at` for financial metrics, but `orders.created_at` for order-count metrics. This distinction matters: an order created today but confirmed next month is reported under creation date for order counts, but under confirmation date for revenue.
- **Status enums** drive the dimension tables (`order_items.status`, `payments.status`, `shipments.status`). The application enforces the closed set on write (FR-14.1).

### 10.2 Polymorphic Joins

For `media` (polymorphic), `notifications` (polymorphic), report queries must pivot per `model_type` / `notifiable_type`. A generalized `morphTo` query is straightforward in Eloquent but degrades in raw SQL. v1.2 limits polymorphic usage to a few well-bounded collections — the reports reflect this.

### 10.3 Snapshotted Prices

All revenue is computed from `order_items.unit_price * order_items.quantity`. Even if a designer changes `design_product_mappings.final_price` later, the historical revenue is preserved (NFR-2).

### 10.4 UUID Lookups

Where reports need to look up by `user_id` UUID, the queries include `WHERE users.id = ?` (CHAR(36) UUID). The 9 public-facing tables use UUID PKs; the FK columns are also `uuid` type. Performance is acceptable at the expected scale (PRD NFR-5).

### 10.5 Indexes That Drive Reports

These indexes were created per FR-16 to make the above reports fast:

| Report                        | Index used                                                                        |
| ----------------------------- | --------------------------------------------------------------------------------- |
| Revenue by day                | `payments.confirmed_at`, `orders.created_at`                                      |
| Revenue by category           | `designs.category_id`, `design_product_mappings(design_id, product_template_id)`  |
| Revenue by designer           | `designs.designer_id`, `users.role`                                               |
| Revenue by printer            | `order_items.printer_provider_id`, `product_templates.printer_provider_id`        |
| Order Items Needing Action    | `order_items.printer_provider_id`, `order_items.status`, `order_items.deleted_at` |
| Designer / Printer Onboarding | `users.role`, `users.created_at`                                                  |
| Cart Abandonment              | `cart_items.user_id`, `cart_items.updated_at`                                     |
| Failed / Stuck Shipments      | `shipments.status`, `shipments.shipped_at`                                        |
| Stuck Payments                | `payments.status`, `payments.created_at`                                          |
| Unread Notifications          | `notifications.read_at`, `notifications(notifiable_type, notifiable_id)`          |

Missing indexes → query performance issues. The schema spec includes these per FR-16.

### 10.6 PII Protection in Reports

- Designer reports **never** include customer name / email / phone.
- Printer reports **only** include shipping_address line / city / country (snapshot) — never customer email / phone.
- Admin reports **filter** customer PII by default; admin can opt-in to view email for support cases.
- Customer reports **only** show the customer's own data.

### 10.7 Export Formats

Every report endpoint supports:

- `Accept: application/json` — default JSON response
- `?format=csv` — CSV stream (for spreadsheet export)
- `?format=xlsx` — XLSX (future extension via `maatwebsite/excel`)

For large reports (>10k rows), the response is paginated (default 50 per page) and the export is queued as a job with an email notification on completion.

### 10.8 Caching

| Report                        | Cache TTL     |
| ----------------------------- | ------------- |
| Admin overview dashboard      | 60 seconds    |
| Designer dashboard            | 60 seconds    |
| Printer work queue            | 0 (real-time) |
| Cart abandonment              | 6 hours       |
| Revenue by day                | 1 hour        |
| Revenue by month              | 1 day         |
| Designer / printer statements | 6 hours       |
| Stick / failure reports       | 5 minutes     |

---

## 11. Report-to-Table Reference (Quick Map)

| Report                          | Primary tables                                                                                                              |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Platform overview               | `users`, `orders`, `payments`, `order_items`, `designs`                                                                     |
| Revenue by day                  | `orders`, `order_items`, `payments`                                                                                         |
| Revenue by category             | `categories`, `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`                                     |
| Revenue by designer             | `users`, `designer_profiles`, `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`                     |
| Revenue by printer              | `users`, `printer_provider_profiles`, `order_items`, `orders`, `payments`                                                   |
| Conversion funnel               | `cart_items`, `orders`, `order_items`                                                                                       |
| Order status distribution       | `orders`                                                                                                                    |
| Order item status distribution  | `order_items`                                                                                                               |
| Payment method distribution     | `payments`, `orders`                                                                                                        |
| Shipping country distribution   | `orders`                                                                                                                    |
| AOV                             | `orders`, `payments`                                                                                                        |
| Top designs by revenue          | `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`                                                   |
| Top templates by revenue        | `product_templates`, `design_product_mappings`, `order_items`, `orders`, `payments`                                         |
| Most-used tags                  | `tags`, `design_tag`, `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`                             |
| Customer cohort                 | `users`, `orders`                                                                               |
| CLV                             | `users`, `orders`, `payments`                                                                                               |
| Cancellation rate               | `orders`, `order_items`                                                                                                     |
| Delivery performance            | `shipments`, `delivery_companies`                                                                                           |
| Designer dashboard              | `designs`, `design_product_mappings`, `order_items`, `orders`, `payments`                                                   |
| Designer revenue by mapping     | `design_product_mappings`, `designs`, `product_templates`, `order_items`, `orders`, `payments`                              |
| Designer payout                 | `orders`, `order_items`, `design_product_mappings`, `designs`, `payments`, `settings`                                       |
| Printer dashboard               | `order_items`, `orders`, `shipments`, `payments`                                                                            |
| Printer work queue              | `order_items`, `orders`, `design_product_mappings`, `designs`, `product_templates`, `product_variants`                      |
| Printer revenue by template     | `product_templates`, `order_items`, `orders`, `payments`                                                                    |
| Printer payout                  | `order_items`, `orders`, `payments`, `settings`                                                                             |
| Customer order history          | `orders`, `order_items`, `payments`                                                                                         |
| Active shipments                | `shipments`, `orders`, `delivery_companies`                                                                                 |
| Customer spending               | `orders`, `payments`                                                                                                        |
| Failed / stuck shipments        | `shipments`, `orders`                                                                                                       |
| Stuck payments                  | `payments`, `orders`                                                                                                        |
| Awaiting printer                | `order_items`                                                                                                               |
| Cart abandonment                | `cart_items`, `orders`, `users`                                                                                             |
| Unread notifications            | `notifications`                                                                                                             |
| Soft-delete audit               | `users`, `designs`, `product_templates`, `product_variants`, `design_product_mappings`, `orders`, `order_items`, `payments` |
| Designer profile integrity      | `users`, `designer_profiles`, `printer_provider_profiles`                                                                   |
| Orphaned media                  | `media`                                                                                                                     |
| Order items without shipment    | `order_items`, `shipments`                                                                                                  |
| Cart items on inactive variants | `cart_items`, `product_variants`                                                                                            |
| Soft-delete ratio               | all soft-deletable tables                                                                                                   |

---

## 12. Versioning Notes

- **v1.2** — this catalog. Reports are computed on read from the live schema. No data warehouse.
- **Backward-incompatible changes from v1.1** — the v1.1 RBAC schema (role, permission, role_permission, user_role) is gone. Reports that depended on those for "user by permission" filters now use the simple `users.role` enum.
- **Future extensions** — multi-currency, refunds, wishlist, clickstream, AI mockups, custom-order analytics. Each would extend this catalog.
