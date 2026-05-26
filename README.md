# Books Manager – WordPress Plugin

A simple WordPress plugin to manage and display a collection of books. Only logged-in users can view the books.

---

## Folder Structure

```
books-manager/
├── books-manager.php       
├── templates/
│   └── single-books.php    
├── assets/
│   └── css/
│       └── style.css        
└── README.md
```

---

## What We Built

### 1. Custom Post Type – Books
We registered a new post type called **Books** in WordPress just like how WordPress has Posts and Pages by default. Now there is a **Books** section in the admin sidebar where we can add and manage books.

Each book has the following fields:
- **Title** — default WordPress field
- **Description** — using the WordPress WYSIWYG editor
- **Author** — a text input field
- **Genre** — a dropdown with options like Fiction, Non-Fiction, Sci-Fi, Fantasy etc.
- **Published Date** — a date picker field

These extra fields (Author, Genre, Published Date) are saved using WordPress **custom meta boxes**.

---

### 2. Access Restriction
We made sure that only logged-in users can see the books. If a guest user tries to open any book page they are automatically redirected to the WordPress login page. After login they are sent back to the page they tried to visit.

We did this in 3 ways in the code:

**1 – Page Redirect**
Using `template_redirect` hook we check if the user is logged in before any page loads. If not logged in and they are on a book page or books listing page they are redirected to login.

```php
add_action('template_redirect', function () {
    if (!is_user_logged_in()) {
        if (is_singular('books') || is_post_type_archive('books')) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }
});
```

**2 – Shortcode Check**
The `[books_list]` shortcode also checks if the user is logged in. If not it shows a message with a login link instead of showing the books.

---

### 3. Books Listing Page – Shortcode
We created a shortcode `[books_list]` that can be placed on any WordPress page. It shows all books in a grid with:
- Book title linked to the single book page
- Author name
- Genre
- Published date
- Short description

It loads **5 books per page** with pagination buttons.

---

### 4. Search and Filter (Bonus)
We added a search and filter bar on the books listing page. Users can filter books by:
- **Title** — type any keyword
- **Genre** — select from dropdown
- **Author** — type author name

All filtering happens via **AJAX** so the page does not reload. The results update instantly.

---

### 5. Single Book Template
We created a custom template file `single-books.php` that shows the full details of a single book:
- Book title
- Author
- Genre
- Published date
- Full description

This template is loaded automatically by the plugin when a user visits a single book page.

---


### 6. Responsive Design
The CSS is written so the books listing page and single book page look good on both **mobile and desktop** using CSS grid and media queries.

---

## How to Install

1. Download this folder
2. Copy the `books-manager` folder and paste it inside your WordPress at:
   ```
   wp-content/plugins/books-manager/
   ```
3. Go to **WordPress Admin → Plugins**
4. Find **Books Manager** and click **Activate**
5. Go to **Settings → Permalinks** and click **Save Changes**

---

## How to Add a Book

1. Go to **Books → Add New** in the WordPress admin sidebar
2. Fill in the **Title** and **Description** in the main editor
3. In the **Book Details** box fill in **Author**, **Genre** and **Published Date**
4. Click **Publish**

---

## How to Show Books on a Page

1. Go to **Pages → Add New**
2. Give the page a title like **Books Library**
3. Paste this shortcode in the page body:
   
   [books_list]
   
4. Click **Publish**

---

