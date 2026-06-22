# GlowBook Booking System
## User Guide for Business Owners

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Understanding the Dashboard](#understanding-the-dashboard)
3. [Managing Bookings](#managing-bookings)
4. [Managing Services](#managing-services)
5. [Service Categories](#service-categories)
6. [Before & After Appointment Workflow](#before--after-appointment-workflow)
7. [Quick Reference](#quick-reference)

---

## Getting Started

### Logging In

1. Go to your website's admin area: `yourwebsite.com/wp-admin`
2. Enter your username and password
3. You'll be taken directly to the **GlowBook Dashboard**

![Login Screen Placeholder]
> *Screenshot: WordPress login page*

---

## Understanding the Dashboard

When you log in, you'll see the **GlowBook Dashboard** - your command center for managing appointments.

### Dashboard Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│  BOOKING OVERVIEW                                    [+ Add Booking]│
│  Good day, [Your Name]                                              │
│  Here's what is happening with your appointments.                   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐            │
│  │    3     │  │    12    │  │    2     │  │  $450    │            │
│  │Appts Today│ │This Week │  │ Pending  │  │Deposits  │            │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘            │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│  TODAY'S SCHEDULE              │  UPCOMING APPOINTMENTS            │
│  ─────────────────             │  ──────────────────────           │
│  Time │ Customer │ Service     │  Date  │ Customer │ Service       │
│  9:00 │ Jane D.  │ Box Braids  │  Jun 24│ Lisa M.  │ Knotless      │
│  1:30 │ Mary S.  │ Locs        │  Jun 25│ Kim W.   │ Twist         │
│                                │                                    │
└─────────────────────────────────────────────────────────────────────┘
```

### Dashboard Stats Explained

| Stat | What It Means |
|------|---------------|
| **Appointments Today** | Number of bookings scheduled for today |
| **This Week** | Total bookings for the current week |
| **Awaiting Confirmation** | Bookings that need your approval (Pending status) |
| **Deposits This Month** | Total deposit payments received this month |

---

## Managing Bookings

### How to Create a New Booking

**Step 1:** Click the **"Add Booking"** button on the dashboard (or go to GlowBook → Bookings → Add New)

![Add Booking Button]
> *Screenshot: Add Booking button location*

**Step 2:** Fill in the booking details

```
┌─────────────────────────────────────────────────────────────────────┐
│  ADD NEW BOOKING                                                    │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  BOOKING DETAILS                    │  BOOKING STATUS               │
│  ──────────────                     │  ──────────────               │
│                                     │                               │
│  Service: [Select Service ▼]        │  Status: [Confirmed ▼]        │
│                                     │                               │
│  Date: [📅 Pick Date]               │  ┌─────────────────────────┐  │
│                                     │  │ ○ Pending               │  │
│  Time: [🕐 Select Time ▼]           │  │ ● Confirmed             │  │
│                                     │  │ ○ Completed             │  │
│  Duration: 180 minutes (auto)       │  │ ○ Cancelled             │  │
│                                     │  │ ○ No Show               │  │
│  ──────────────────────────────     │  └─────────────────────────┘  │
│                                     │                               │
│  CUSTOMER INFORMATION               │                               │
│  ────────────────────               │                               │
│                                     │                               │
│  Name: [________________]           │                               │
│  Email: [________________]          │                               │
│  Phone: [________________]          │                               │
│                                     │                               │
└─────────────────────────────────────────────────────────────────────┘
```

**Required Fields:**
- **Service** - Select the service being booked
- **Date** - Choose the appointment date
- **Time** - Select the start time
- **Customer Name** - Client's full name
- **Customer Phone** - Contact number (for reminders)
- **Customer Email** - For confirmation emails

**Step 3:** Set the booking status (usually "Confirmed" for manual bookings)

**Step 4:** Click **"Publish"** to save the booking

---

### How to View All Bookings

1. Go to **GlowBook → Bookings** in the menu
2. You'll see a list of all bookings

```
┌─────────────────────────────────────────────────────────────────────┐
│  BOOKINGS                                           [+ Add New]     │
├─────────────────────────────────────────────────────────────────────┤
│  All (45) │ Pending (3) │ Confirmed (12) │ Completed (28)           │
├─────────────────────────────────────────────────────────────────────┤
│  □ │ Booking          │ Customer    │ Service    │ Date    │Status │
│  ──┼──────────────────┼─────────────┼────────────┼─────────┼───────│
│  □ │ #1234            │ Jane Doe    │ Box Braids │ Jun 22  │Pending│
│  □ │ #1233            │ Mary Smith  │ Locs       │ Jun 22  │Confirm│
│  □ │ #1232            │ Lisa Jones  │ Knotless   │ Jun 21  │Complete│
└─────────────────────────────────────────────────────────────────────┘
```

### Filtering Bookings

Use the tabs at the top to filter by status:
- **All** - Shows every booking
- **Pending** - Bookings awaiting your confirmation
- **Confirmed** - Approved upcoming appointments
- **Completed** - Finished appointments
- **Cancelled** - Cancelled bookings

---

### How to Edit a Booking

1. Click on the booking title or the **"Edit"** link
2. Make your changes
3. Click **"Update"** to save

### Changing Booking Status

The status tells you and the customer where the booking stands:

| Status | Meaning | When to Use |
|--------|---------|-------------|
| **Pending** | Waiting for approval | New online bookings that need review |
| **Confirmed** | Approved and scheduled | After you approve a booking |
| **Completed** | Service was delivered | After the appointment is done |
| **Cancelled** | Appointment cancelled | Client or you cancelled |
| **No Show** | Client didn't show up | Client missed appointment |

**To change status:**
1. Open the booking
2. Find the **"Booking Status"** box on the right
3. Select the new status
4. Click **"Update"**

![Status Change]
> *Screenshot: Booking status dropdown*

---

## Managing Services

### How to Add a New Service

1. Go to **GlowBook → Services → Add New**

```
┌─────────────────────────────────────────────────────────────────────┐
│  ADD NEW SERVICE                                                    │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Service Name: [Box Braids - Medium Length________________]         │
│                                                                     │
│  Description:                                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ Beautiful medium-length box braids. Includes washing,       │   │
│  │ conditioning, and styling. Perfect for protective styling.  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ─────────────────────────────────────────────────────────────────  │
│                                                                     │
│  SERVICE DETAILS                    │  FEATURED IMAGE               │
│  ───────────────                    │  ──────────────               │
│                                     │                               │
│  Duration: [180] minutes            │  ┌─────────────────────┐      │
│                                     │  │                     │      │
│  Price: [$___] (display only)       │  │   [Set Image]       │      │
│                                     │  │                     │      │
│  Deposit: [$75.00]                  │  └─────────────────────┘      │
│                                     │                               │
│  Category: [☑ Braids]               │                               │
│            [☐ Locs]                 │                               │
│            [☐ Twists]               │                               │
│                                     │                               │
│  ☑ Show on Frontend                 │                               │
│  ☑ Allow Online Booking             │                               │
│                                     │                               │
└─────────────────────────────────────────────────────────────────────┘
```

2. Fill in the details:

| Field | Description | Example |
|-------|-------------|---------|
| **Service Name** | Clear, descriptive name | "Box Braids - Medium Length" |
| **Description** | What's included, what to expect | "Includes wash, condition, and styling..." |
| **Duration** | How long in minutes | 180 (for 3 hours) |
| **Price** | Display price (informational) | $250 |
| **Deposit** | Required deposit amount | $75 |
| **Category** | Service category | Braids |
| **Featured Image** | Photo of the style | Upload a portfolio image |

3. Check these boxes:
   - ✅ **Show on Frontend** - Makes it visible on your website
   - ✅ **Allow Online Booking** - Lets clients book online

4. Click **"Publish"**

---

### How to Edit a Service

1. Go to **GlowBook → Services**
2. Click on the service name
3. Make your changes
4. Click **"Update"**

### Tips for Service Setup

✅ **DO:**
- Use clear, descriptive names
- Include what's included in the description
- Set accurate duration times
- Add beautiful portfolio photos
- Set appropriate deposit amounts

❌ **DON'T:**
- Use vague names like "Style 1"
- Forget to set the duration
- Leave the deposit at $0 if you require one
- Skip the featured image

---

## Service Categories

Categories help organize your services and make it easier for clients to find what they want.

### How to Add a Category

1. Go to **GlowBook → Services → Categories**

```
┌─────────────────────────────────────────────────────────────────────┐
│  SERVICE CATEGORIES                                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ADD NEW CATEGORY              │  EXISTING CATEGORIES               │
│  ─────────────────             │  ────────────────────              │
│                                │                                    │
│  Name: [Braids__________]      │  Name        │ Description │Count │
│                                │  ────────────┼─────────────┼──────│
│  Slug: [braids__________]      │  Braids      │ All braid..│  8   │
│  (auto-generated)              │  Locs        │ Loc styles │  5   │
│                                │  Twists      │ Twist var..│  4   │
│  Description:                  │  Crochet     │ Crochet... │  3   │
│  [All braiding styles____]     │                                   │
│                                │                                    │
│  [Add New Category]            │                                    │
│                                │                                    │
└─────────────────────────────────────────────────────────────────────┘
```

2. Enter the category name
3. Add a brief description (optional)
4. Click **"Add New Category"**

### Suggested Categories

- **Braids** - Box braids, knotless, tribal, etc.
- **Locs** - Starter locs, retwists, interlocks
- **Twists** - Senegalese, Marley, passion twists
- **Crochet** - Crochet braids and styles
- **Natural Styles** - Cornrows, flat twists, updos
- **Kids** - Children's styling services

---

## Before & After Appointment Workflow

### Before the Appointment

#### When a New Booking Comes In:

```
┌─────────────────────────────────────────────────────────────────────┐
│  📬 NEW BOOKING RECEIVED                                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. CHECK YOUR DASHBOARD                                            │
│     └── Look for bookings with "Pending" status                     │
│                                                                     │
│  2. REVIEW THE BOOKING                                              │
│     └── Click to open and check:                                    │
│         • Service requested                                         │
│         • Date and time                                             │
│         • Customer notes/special requests                           │
│         • Deposit payment status                                    │
│                                                                     │
│  3. CONFIRM OR CONTACT                                              │
│     └── If everything looks good:                                   │
│         • Change status to "Confirmed"                              │
│         • Customer receives confirmation email                      │
│                                                                     │
│     └── If you need more info:                                      │
│         • Call/text the customer                                    │
│         • Use the phone number in the booking                       │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

#### Checklist Before Confirming:

- [ ] Check your calendar - are you available?
- [ ] Is the service duration realistic for your schedule?
- [ ] Has the deposit been paid? (Check payment status)
- [ ] Any special requests you can accommodate?
- [ ] Do you need to contact the client for hair consultation?

#### Day Before the Appointment:

- [ ] Review tomorrow's schedule on the dashboard
- [ ] Check customer notes for any special requests
- [ ] Prepare supplies needed for the services
- [ ] Send reminder if needed (optional - system may auto-send)

---

### During the Appointment

- Deliver excellent service!
- Note any special products used or techniques
- Take before/after photos (with permission) for your portfolio

---

### After the Appointment

#### Completing the Booking:

```
┌─────────────────────────────────────────────────────────────────────┐
│  ✅ COMPLETING A BOOKING                                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  STEP 1: Open the booking                                           │
│          GlowBook → Bookings → Click on the booking                 │
│                                                                     │
│  STEP 2: Change status to "Completed"                               │
│          ┌─────────────────────────┐                                │
│          │ Booking Status          │                                │
│          │ ───────────────         │                                │
│          │ ○ Pending               │                                │
│          │ ○ Confirmed             │                                │
│          │ ● Completed  ← SELECT   │                                │
│          │ ○ Cancelled             │                                │
│          │ ○ No Show               │                                │
│          └─────────────────────────┘                                │
│                                                                     │
│  STEP 3: Add admin notes (optional but recommended)                 │
│          ┌─────────────────────────────────────────┐                │
│          │ Admin Notes                             │                │
│          │ ─────────────                           │                │
│          │ Used 5 packs of hair.                   │                │
│          │ Client wants slightly longer next time. │                │
│          │ Great client - very patient!            │                │
│          └─────────────────────────────────────────┘                │
│                                                                     │
│  STEP 4: Click "Update"                                             │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

#### Post-Appointment Checklist:

- [ ] Mark booking as "Completed"
- [ ] Add any admin notes for your records
- [ ] Collect remaining balance (if applicable)
- [ ] Ask for a review or referral
- [ ] Upload photos to your portfolio (optional)

---

### Handling No-Shows

If a client doesn't show up:

1. Wait 15-30 minutes past the appointment time
2. Attempt to contact the client
3. If no response, open the booking
4. Change status to **"No Show"**
5. Add notes about attempts to contact
6. Click **"Update"**

> **Tip:** Your deposit policy protects you from no-shows. Consider keeping the deposit as a cancellation fee.

---

### Handling Cancellations

If a client needs to cancel:

1. Open the booking
2. Change status to **"Cancelled"**
3. Add notes about the cancellation
4. Click **"Update"**
5. Process any refund according to your policy

---

## Quick Reference

### Booking Status Flow

```
    ┌──────────┐
    │  NEW     │  Client books online
    │ BOOKING  │
    └────┬─────┘
         │
         ▼
    ┌──────────┐     ┌──────────┐
    │ PENDING  │────▶│CANCELLED │  You or client cancels
    └────┬─────┘     └──────────┘
         │
         ▼
    ┌──────────┐     ┌──────────┐
    │CONFIRMED │────▶│ NO SHOW  │  Client doesn't appear
    └────┬─────┘     └──────────┘
         │
         ▼
    ┌──────────┐
    │COMPLETED │  Service delivered successfully!
    └──────────┘
```

### Menu Navigation

| Menu Item | What It Does |
|-----------|--------------|
| **GlowBook** (Dashboard) | Overview of your bookings and stats |
| **Bookings** | View, add, and manage all appointments |
| **Services** | Add and edit your service offerings |
| **Categories** | Organize services into groups |

### Keyboard Shortcuts

| Action | How To |
|--------|--------|
| Save/Update | `Ctrl + S` (Windows) or `Cmd + S` (Mac) |
| Quick search | Click the search icon in the top bar |

### Need Help?

If you have questions or issues:
1. Check this guide first
2. Contact your website administrator
3. Email support with specific details about the issue

---

## Appendix: Status Color Guide

When viewing bookings, status colors help you quickly identify booking states:

| Status | Color | Meaning |
|--------|-------|---------|
| 🟡 **Pending** | Yellow/Orange | Needs your attention |
| 🟢 **Confirmed** | Green | Good to go |
| 🔵 **Completed** | Blue | Finished |
| 🔴 **Cancelled** | Red | Cancelled |
| ⚫ **No Show** | Gray/Black | Client didn't show |

---

*Document Version: 1.0*
*Last Updated: June 2026*
*GlowBook Booking System*
