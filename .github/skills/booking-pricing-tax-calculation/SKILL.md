---
title: Booking Pricing & Tax Calculation (Laravel)
category: Backend
stack: Laravel, PHP, REST API
level: Intermediate
status: Active
last_updated: 2026-01-24
---

# Booking Pricing & Tax Calculation (Laravel)

This skill covers how to design and implement a robust backend pricing system for car bookings, including rental duration logic, tax calculation, and strict backend validation.

The goal is to ensure **all business logic lives in the backend**, making the system secure, predictable, and easy to maintain.

---

## Core Concepts

### 1. Required Fields

| Field        | Type    | Rules                      |
| ------------ | ------- | -------------------------- |
| rental_days  | integer | required, min: 1           |
| allowance_km | integer | required, positive number  |
| tax_rate     | decimal | percentage (e.g. 15 = 15%) |

---

## Pricing Rules

Pricing depends **only on `rental_days`**.

### Rule Engine

- If `rental_days <= 7`  
  → Daily price

- If `rental_days > 7 && <= 30`  
  → Weekly price

- If `rental_days > 30`  
  → Monthly price

### Formula

```php
if ($rental_days <= 7) {
    $subtotal = $rental_days * $price_per_day;
} elseif ($rental_days <= 30) {
    $subtotal = $rental_days * $price_per_week;
} else {
    $subtotal = $rental_days * $price_per_month;
}
```
