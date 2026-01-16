# SellNow - Digital Marketplace

## The Manifesto

This document articulates my engineering philosophy as demonstrated through the refactoring of this inherited codebase.

---

## 1. The Audit: Honest Critique of the Inherited Code

### Critical Security Issues (Severity: 🔴 Critical)

| Issue | Location | Risk |
|-------|----------|------|
| **Plain-text passwords** | `AuthController::register()` | Passwords stored as-is. One database breach exposes all users. |
| **SQL Injection** | `PublicController::profile()` | Direct variable interpolation: `"WHERE user_id = $user->id"` |
| **No CSRF protection** | All forms | Any malicious site could submit forms on behalf of logged-in users |
| **Insecure session handling** | `AuthController::dashboard()` | Missing `exit` after redirect allows code execution |

### Architectural Issues (Severity: 🟡 High)

| Issue | Description |
|-------|-------------|
| **God File** | `public/index.php` is a 135-line switch statement handling all routing |
| **No separation of concerns** | Controllers contain SQL queries, business logic, and presentation logic |
| **No data layer** | Raw PDO calls scattered across controllers |
| **Hardcoded dependencies** | Database credentials in source code, payment providers hardcoded as array |
| **No input validation** | `$_POST` values used directly without sanitization |

### Code Quality Issues (Severity: 🟢 Medium)

| Issue | Example |
|-------|---------|
| **Inconsistent naming** | `Full_Name` vs `username`, `id` vs `product_id`, `Carts` vs `orders` |
| **No type safety** | No parameter or return types on any method |
| **Mixed concerns in templates** | jQuery and business logic embedded in Twig templates |
| **No error handling strategy** | Mix of `die()`, silent failures, and unhandled exceptions |

---

## 2. The Priority Matrix: Why X Instead of Y?

### My Philosophy: Security First, Architecture Second, Features Never

I prioritized based on **risk vs effort** and **foundation vs decoration**:

```
                        HIGH IMPACT
                            │
    ┌───────────────────────┼───────────────────────┐
    │                       │                       │
    │   Password Hashing    │   Router Class        │
    │   SQL Injection Fix   │   Service Layer       │
    │                       │                       │
LOW ├───────────────────────┼───────────────────────┤ HIGH
EFFORT                      │                       EFFORT
    │                       │                       │
    │   CSRF Protection     │   Full Test Suite     │
    │   Input Validation    │   CI/CD Pipeline      │
    │                       │                       │
    └───────────────────────┼───────────────────────┘
                            │
                        LOW IMPACT
```

### What I Did (In Order)

| Priority | Task | Rationale |
|----------|------|-----------|
| **1** | Password Hashing | 5-minute fix that prevents catastrophic data breach |
| **2** | SQL Injection Fix | Critical vulnerability, trivial to exploit |
| **3** | Entities/Models | Foundation for everything else; defines the language of the domain |
| **4** | Repository Pattern | Separates data access; enables testing; single source of truth for queries |
| **5** | Payment Interface | Demonstrates extensibility philosophy; critical business component |
| **6** | Router Class | Replaces the switch statement; enables clean route management |
| **7** | Validation Layer | Ensures data integrity from HTTP to database |
| **8** | CSRF Protection | Completes the security foundation |

### What I Deliberately Did NOT Do

| Skipped | Reason |
|---------|--------|
| **UI/Feature completion** | Task explicitly states "We do not want a feature-complete app" |
| **Full test suite** | Time constraint; architecture demonstrates testability |
| **Database migrations** | Schema exists; refactoring schema would be "erasure not evolution" |
| **Dependency Injection Container** | Would add complexity; manual DI is sufficient at this scale |
| **ORM integration** | Repositories provide the abstraction; Doctrine would be overkill |

---

## 3. The Trade-offs: What I Sacrificed

### Trade-off 1: New Architecture Fully Integrated

**Choice:** Replaced the legacy switch statement with a proper Router while maintaining all functionality

**What I gained:**
- Clean, working Router pattern (from 135-line switch to ~50 lines of route definitions)
- Demonstrable patterns that actually run in production
- Clear separation between route definition and handler logic

**What I sacrificed:**
- Time spent on integration testing
- Some edge cases may need additional handling

**Why:** The task says "Evolution, Not Erasure." I kept the old switch statement as a comment in `index.php` to show the before/after comparison, while the new Router handles all requests. This demonstrates bridging legacy to modern code.

### Trade-off 2: Depth vs Breadth

**Choice:** Deep implementation of key patterns vs shallow coverage of everything

**What I gained:**
- Payment system with proper interface, multiple implementations, result objects
- Validation system with extensible rules and clean API
- Repositories with rich query methods

**What I sacrificed:**
- No NotificationInterface (mentioned in task as example)
- No caching layer
- No queue system

**Why:** One well-implemented pattern teaches more than five half-implemented ones.

### Trade-off 3: Pragmatism vs Purity

**Choice:** Practical security fixes over architectural perfection

**What I gained:**
- Immediate security improvements (passwords, SQL injection)
- Working CSRF system
- Input sanitization

**What I sacrificed:**
- Controllers still have some mixed responsibilities
- Not all endpoints use the new validation
- Some code duplication remains

**Why:** A secure "imperfect" system is better than an elegant vulnerable one.

---

## 4. Architecture Overview

### Directory Structure

```
src/
├── Config/
│   └── Database.php          # Database connection (Singleton)
│
├── Controllers/              # HTTP handlers (existing, modified for security)
│   ├── AuthController.php    # [MODIFIED] Password hashing, dashboard products
│   ├── CartController.php    # Cart management
│   ├── CheckoutController.php # Checkout flow
│   ├── ProductController.php # Product CRUD
│   └── PublicController.php  # [MODIFIED] SQL injection fix, public shop
│
├── Entities/                 # [NEW] Domain objects
│   ├── User.php
│   ├── Product.php
│   ├── CartItem.php
│   └── Order.php
│
├── Http/                     # [NEW] HTTP abstractions
│   ├── Request.php           # Request wrapper
│   ├── Response.php          # Response helpers
│   └── Router.php            # Route registration & dispatching
│
├── Payment/                  # [NEW] Payment gateway system
│   ├── PaymentGatewayInterface.php
│   ├── PaymentResult.php
│   ├── PaymentService.php
│   └── Gateways/
│       ├── StripeGateway.php
│       ├── PayPalGateway.php
│       └── RazorpayGateway.php
│
├── Repositories/             # [NEW] Data access layer
│   ├── RepositoryInterface.php
│   ├── UserRepository.php
│   ├── ProductRepository.php
│   └── OrderRepository.php
│
├── Security/                 # [NEW] Security utilities
│   ├── Csrf.php              # CSRF token generation/validation
│   ├── CsrfMiddleware.php    # Request middleware
│   └── Sanitizer.php         # Input sanitization
│
├── Services/                 # Business logic (ready for expansion)
│
└── Validation/               # [NEW] Input validation
    ├── Validator.php
    ├── ValidationResult.php
    └── Rules/
        ├── RuleInterface.php
        └── UniqueRule.php
```

### Design Patterns Used

| Pattern | Implementation | Purpose |
|---------|---------------|---------|
| **Repository** | `UserRepository`, `ProductRepository`, `OrderRepository` | Abstracts data access; single responsibility |
| **Strategy** | `PaymentGatewayInterface` + implementations | Swappable payment providers |
| **Factory** | `PaymentService` | Creates and manages payment gateways |
| **Singleton** | `Database` | Single database connection |
| **Value Object** | `PaymentResult`, `ValidationResult` | Immutable result containers |
| **Builder** | Entity setters return `$this` | Fluent interface for object construction |

---

## 5. Key Implementations

### Security: Password Hashing

```php
// Before (VULNERABLE)
if ($password == $user['password']) { ... }

// After (SECURE)
if (password_verify($password, $user['password'])) { ... }
```

### Security: SQL Injection Fix

```php
// Before (VULNERABLE)
$this->db->query("SELECT * FROM products WHERE user_id = $user->id");

// After (SECURE)
$stmt = $this->db->prepare("SELECT * FROM products WHERE user_id = ?");
$stmt->execute([$user->id]);
```

### Architecture: Payment Gateway Interface

```php
interface PaymentGatewayInterface
{
    public function getName(): string;
    public function charge(Order $order, array $details): PaymentResult;
    public function refund(string $transactionId, float $amount): PaymentResult;
}

// Usage: Swap providers without changing business logic
$payment = new PaymentService();
$result = $payment->processPayment($order, $details, 'stripe');
// or
$result = $payment->processPayment($order, $details, 'paypal');
```

### Architecture: Validation

```php
$validator = Validator::make($request->all(), [
    'email' => 'required|email',
    'password' => 'required|min:8',
    'username' => 'required|alphanumeric|min:3|max:20',
]);

if ($validator->fails()) {
    return $validator->errors();
}

$validated = $validator->validated();
```

---

## 6. Performance Observations

### Identified Bottlenecks (Not Fixed - Documented)

| Issue | Location | Impact |
|-------|----------|--------|
| **Potential N+1** | Product listing with user data | Would need eager loading |
| **Session-based cart** | `CartController` | Doesn't scale to multiple servers |
| **No query caching** | All repositories | Every request hits database |
| **Synchronous payments** | `CheckoutController` | Should be queued |

### My Philosophy on Performance

> "Premature optimization is the root of all evil" - Donald Knuth

I deliberately did not optimize because:
1. No profiling data exists
2. The app doesn't have real traffic
3. Architecture changes enable future optimization
4. Repositories provide the abstraction layer needed to add caching later

---

## 7. What's Left (Known Limitations)

| Item | Status | Note |
|------|--------|------|
| File upload validation | ❌ Not implemented | Would add MIME type checking |
| Environment configuration | ❌ Hardcoded | Would use `.env` with `vlucas/phpdotenv` |
| Router integration | ✅ Fully wired | Replaced legacy switch statement |
| Service layer | ⚠️ Folder only | Pattern demonstrated in PaymentService |
| Dashboard products | ✅ Working | Shows user's products with prices |
| Cart functionality | ✅ Working | Add to cart, view cart, checkout flow |
| Public shop pages | ✅ Working | Shows seller's products with Add to Cart |

---

## 8. Working Features

The application is fully functional with the following user flows:

| Feature | Status | Description |
|---------|--------|-------------|
| **User Registration** | ✅ | Register with email, username, password (bcrypt hashed) |
| **User Login** | ✅ | Secure login with password verification |
| **Dashboard** | ✅ | View your products, add new products |
| **Add Products** | ✅ | Create digital products with title, price, image |
| **Public Shop** | ✅ | Visit `/{username}` to see seller's products |
| **Shopping Cart** | ✅ | Add to cart, view cart, clear cart |
| **Checkout Flow** | ✅ | Select payment provider, mock payment |

---

## 9. Running the Project

### Requirements
- PHP 8.0+
- MySQL 5.7+ or SQLite
- Composer

### Setup

```bash
# Install dependencies
composer install

# Configure database (edit src/Config/Database.php)
# Default: MySQL with user 'emon', password 'admin', database 'sellnow'

# Start development server
php -S localhost:8000 -t public

# Visit http://localhost:8000
```

---

## 10. Conclusion

This refactoring demonstrates my belief that **good architecture is invisible**. When done right, it:

- Makes the easy things easy
- Makes the hard things possible
- Makes the wrong things impossible

I chose to build **foundations over features** because:
- Security vulnerabilities don't wait for perfect architecture
- Patterns enable future developers to extend without fear
- A codebase should teach, not just function

The code speaks to my values: **security, clarity, extensibility, and pragmatism**.

---

*Refactored with engineering discipline, not framework dependency.*
# sell_now
