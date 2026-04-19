# High-Performance News Aggregator API

A robust RESTful API built with Laravel 11 designed to aggregate, cache, and serve news content efficiently. This project demonstrates mid-level backend architecture, focusing on database optimization, smart caching, and authenticated user states.

---

## Features

-   **Optimized Content Delivery:** Specialized query logic for featured and paginated blog posts.
-   **Smart Global Caching:** Implements Cache::remember to handle high-traffic scenarios (5,000+ concurrent requests) while reducing database load.
-   **Context-Aware Data:** Dynamically detects is_liked status and likes_count for authenticated users without breaking the global cache layer.
-   **Eager Loading & Aggregates:** Prevents N+1 query issues using withCount and withExists for highly performant relationship loading.
-   **Clean API Resources:** Standardized JSON responses via Laravel API Resources.

## Tech Stack

-   **Backend:** Laravel 11.x (PHP 8.2+)
-   **Database:** MySQL / PostgreSQL
-   **Caching:** Redis (Cache-aside pattern)
-   **Authentication:** Laravel Sanctum (Token-based)

---

## Performance Architecture

### The Caching Strategy

To ensure the application remains responsive under heavy load, the main index feed is cached for 60 seconds. This strategy ensures that the database only executes a single query per minute for the most visited pages, serving thousands of other users directly from memory.

### Handling Authenticated State

One of the core challenges was maintaining a global cache while still showing personalized "Like" states for logged-in users. This was solved by:

1. Using withExists logic to check for user-specific interactions.
2. Ensuring the auth()->id() is handled within the API Resource to prevent data leakage between user sessions.

---

## Getting Started

### Prerequisites

-   PHP 8.2 or higher
-   Composer
-   MySQL/PostgreSQL

### Installation

1. **Clone the repository:**
    ```bash
    git clone [https://github.com/yourusername/news-aggregator-api.git](https://github.com/yourusername/news-aggregator-api.git)
    cd news-aggregator-api
    ```
