---
title: "Creating Authors via the REST API"
order: 39
doc_category: publishpress-authors
---

# Creating Authors via the REST API

Table of Contents

- [1. Post Authors Field (GET)](#0-toc-title)
- [2. Create Author (POST)](#1-toc-title)
- [3. Get Single Author (GET)](#2-toc-title)
- [4. Update Author (PUT)](#3-toc-title)
- [5. Partial Update Author (PATCH)](#4-toc-title)
- [Usage Examples of the REST API](#5-toc-title)[Creating Different Author Types](#6-toc-title)
- [Error Responses](#7-toc-title)[400 Bad Request](#8-toc-title)[403 Forbidden](#9-toc-title)[404 Not Found](#10-toc-title)[409 Conflict](#11-toc-title)
- [Available Author Fields](#12-toc-title)
- [Security Features](#13-toc-title)

This is a guide to create Authors in the [PublishPress Authors](https://publishpress.com/links/authors) plugin using the WordPress Rest API. In the [PublishPress Authors](https://publishpress.com/links/authors) plugin, authors are created as taxonomies. [This article explains the data structure in PublishPress Authors](https://publishpress.com/knowledge-base/publishpress-authors-database-settings/).

### 1. Post Authors Field (GET) [#](#0-toc-title)

**Endpoint**: Available on all WordPress post endpoints  
**URL**: `/wp-json/wp/v2/posts/{id}` or `/wp-json/wp/v2/posts`  
**Method**: `GET`  
**Description**: Adds an `authors` field to post objects containing author information

**Response Format**:

```
{
  "id": 123,
  "title": {"rendered": "Post Title"},
  "authors": [
    {
      "term_id": 45,
      "user_id": 1,
      "is_guest": 0,
      "slug": "john-doe",
      "display_name": "John Doe",
      "avatar_url": "https://example.com/avatar.jpg",
      "first_name": "John",
      "last_name": "Doe",
      "description": "Author bio..."
    }
  ]
}
```

### 2. Create Author (POST) [#](#1-toc-title)

**Endpoint**: `/wp-json/publishpress-authors/v1/authors`  
**Method**: `POST`  
**Permission**: Requires `ppma_manage_authors` capability

**Request Body**:

```
{
  "display_name": "Jane Smith",
  "user_email": "[email protected]",
  "slug": "jane-smith",
  "author_fields": {
    "first_name": "Jane",
    "last_name": "Smith",
    "description": "Author biography",
    "website": "https://janesmith.com"
  }
}
```

**Parameters**:

- `display_name` (required): Author’s display name
- `user_email` (optional): Email address – if provided, creates a guest author with user account
- `user_id` (optional): Existing WordPress user ID to map to author
- `slug` (optional): URL slug, auto-generated from display_name if not provided
- `author_fields` (optional): Object containing custom author field values

**Author Type Logic**:

- If `user_id` provided > Creates “existing_user” type author
- If `user_email` provided > Creates “new_user” type guest author with account
- If neither > Creates “guest_author” type without user account if enabled

**Success Response (201)**:

```
{
  "term_id": 46,
  "user_id": 15,
  "is_guest": 1,
  "slug": "jane-smith",
  "display_name": "Jane Smith",
  "avatar_url": "https://example.com/avatar.jpg",
  "author_type": "new_user",
  "edit_link": "https://example.com/wp-admin/term.php?taxonomy=author&tag_ID=46",
  "first_name": "Jane",
  "last_name": "Smith",
  "description": "Author biography"
}
```

### 3. Get Single Author (GET) [#](#2-toc-title)

**Endpoint**: `/wp-json/publishpress-authors/v1/authors/{id}`  
**Method**: `GET`  
**Permission**: Public (no authentication required)

**Example**: `/wp-json/publishpress-authors/v1/authors/46`

**Success Response (200)**:

```
{
  "term_id": 46,
  "user_id": 15,
  "is_guest": 1,
  "slug": "jane-smith",
  "display_name": "Jane Smith",
  "avatar_url": "https://example.com/avatar.jpg",
  "author_type": "existing",
  "edit_link": "https://example.com/wp-admin/term.php?taxonomy=author&tag_ID=46",
  "first_name": "Jane",
  "last_name": "Smith"
}
```

### 4. Update Author (PUT) [#](#3-toc-title)

**Endpoint**: `/wp-json/publishpress-authors/v1/authors/{id}`  
**Method**: `PUT`  
**Permission**: Requires `ppma_manage_authors` capability

**Request Body**:

```
{
  "display_name": "Jane Smith Updated",
  "user_email": "[email protected]",
  "author_fields": {
    "description": "Updated biography",
    "website": "https://newsite.com"
  }
}
```

**Success Response (200)**: Same format as GET response with updated data

### 5. Partial Update Author (PATCH) [#](#4-toc-title)

**Endpoint**: `/wp-json/publishpress-authors/v1/authors/{id}`  
**Method**: `PATCH`  
**Permission**: Requires `ppma_manage_authors` capability

**Request Body** (only fields to update):

```
{
  "author_fields": {
    "description": "Just updating the bio"
  }
}
```

## Usage Examples of the REST API [#](#5-toc-title)

Here are some use-cases for creating authors in [PublishPress Authors](https://publishpress.com/links/authors).

### Creating Different Author Types [#](#6-toc-title)

**Guest Author with User Account**:

[gist id=”0e58f08a02c253da398330ac1e304234″]

## Error Responses [#](#7-toc-title)

### 400 Bad Request [#](#8-toc-title)

Invalid data or disabled features:

```
{
  "code": "guest_authors_disabled",
  "message": "Guest authors without user accounts are not enabled.",
  "data": {"status": 400}
}
```

### 403 Forbidden [#](#9-toc-title)

Insufficient permissions:

```
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {"status": 403}
}
```

### 404 Not Found [#](#10-toc-title)

Author doesn’t exist:

```
{
  "code": "author_not_found",
  "message": "Author not found.",
  "data": {"status": 404}
}
```

### 409 Conflict [#](#11-toc-title)

Duplicate data:

```
{
  "code": "slug_exists",
  "message": "An author with this slug already exists.",
  "data": {"status": 409}
}
```

## Available Author Fields [#](#12-toc-title)

The `author_fields` parameter accepts many fields. Common fields include:

- `first_name`
- `last_name`
- `description`
- `website`
- `user_email`
- Custom fields added via Author Fields UI

## Security Features [#](#13-toc-title)

- The `ppma_manage_authors` capability is required for create/update operations.
- Author fields are automatically sanitized based on their field type configuration.
- Security validation prevents editing of administrator accounts through mapped users.
- Permission checks ensure users can only edit authors they have proper capabilities for.
