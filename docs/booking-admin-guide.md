# GlowBook Booking Admin Guide

Welcome to GlowBook! This guide will help you manage bookings, customers, and your schedule effectively.

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard Overview](#dashboard-overview)
3. [Managing the Calendar](#managing-the-calendar)
4. [Setting Your Availability](#setting-your-availability)
5. [Managing Customers](#managing-customers)
6. [Viewing Reports](#viewing-reports)
7. [Booking Statuses Explained](#booking-statuses-explained)
8. [Tips & Best Practices](#tips--best-practices)

---

## Getting Started

When you log in to WordPress, you'll be automatically redirected to your GlowBook Dashboard. As a Booking Admin, you have access to everything you need to manage appointments:

- **Dashboard** - Quick overview of today's schedule and stats
- **Calendar** - Visual booking management
- **Availability** - Set your working hours and blocked dates
- **Customers** - View customer profiles and history
- **Reports** - Track your business performance

> **Note:** You won't see other WordPress features (Posts, Pages, Plugins, etc.) - this is intentional to keep your workspace focused on bookings.

---

## Dashboard Overview

The Dashboard is your home base. Here's what you'll see:

### Quick Stats (Top Row)

| Stat | What It Means |
|------|---------------|
| **Today's Appointments** | Number of bookings scheduled for today |
| **This Week** | Total appointments for the current week |
| **Pending** | Bookings waiting for your confirmation |
| **Monthly Deposits** | Total deposits collected this month |

### Today's Schedule

A table showing all of today's appointments with:
- **Time** - When the appointment starts
- **Customer** - Client name and phone number
- **Service** - What service they booked
- **Status** - Current booking status (color-coded)
- **Actions** - Click "View" to see full details

### Upcoming Appointments

Shows your next scheduled appointments beyond today, so you can prepare in advance.

---

## Managing the Calendar

The Calendar gives you a visual view of all your bookings.

### Filtering Bookings

Use the dropdown filters at the top to narrow your view:
- **Filter by Service** - Show only specific service types
- **Filter by Status** - Show only pending, confirmed, completed, etc.

### Understanding the Color Legend

Each booking status has a unique color:
- **Orange** = Pending (awaiting confirmation)
- **Green** = Confirmed
- **Blue** = Completed
- **Red** = Cancelled
- **Gray** = No-show

### Viewing Booking Details

Click on any booking in the calendar to open the details popup showing:
- Customer name and contact info
- Service booked
- Date and time
- Duration
- Deposit amount
- Any notes from the customer

### Quick Actions

From the booking popup, you can:
- **Edit Booking** - Make changes to the appointment
- **Confirm** - Approve a pending booking
- **Mark Complete** - After the appointment is done
- **Cancel** - Cancel the booking if needed

---

## Setting Your Availability

Control when customers can book appointments.

### Weekly Schedule

Set your regular working hours for each day of the week:

1. **Check the box** next to each day you're available
2. **Set start time** - When you start taking appointments
3. **Set end time** - When your last appointment slot ends
4. Click **Save Schedule**

**Example:**
| Day | Available | Start | End |
|-----|-----------|-------|-----|
| Monday | ✓ | 9:00 AM | 6:00 PM |
| Tuesday | ✓ | 9:00 AM | 6:00 PM |
| Sunday | ✗ | - | - |

### Daily Booking Limits

Prevent overbooking by setting limits:

- **Default Daily Limit** - Maximum bookings per day (set to 0 for unlimited)
- **Per-Day Overrides** - Different limits for specific weekdays
- **Date-Specific Overrides** - Adjust limits for holidays or special occasions

**Example:** If you normally take 5 appointments per day but want to take 8 on Saturdays, set Saturday's override to 8.

### Blocking Dates

Block entire days when you're unavailable:

1. Enter the **date** you want to block
2. Add an optional **reason** (e.g., "Vacation", "Holiday")
3. Click **Add Blocked Date**

Blocked dates will appear in a list below. Click **Remove** to unblock a date.

---

## Managing Customers

View and manage your customer relationships.

### Customer Directory

The main view shows all your customers with:
- **Search bar** - Find customers by name, email, or phone
- **Stats cards** - Total customers, portal-ready, SMS opt-ins
- **Customer cards** - Quick view of each customer

### Customer Profile

Click on a customer to see their full profile:

#### Quick Stats
- Account type (Guest, Portal, or WordPress user)
- When they joined
- Total bookings
- Total amount spent

#### Booking History
- **Recent Bookings** - Their latest appointments
- **Upcoming Appointments** - What's scheduled
- **Past Appointments** - Complete history

#### Profile Details
- Contact information
- Hair type and length (if provided)
- Portal and SMS opt-in status
- Saved payment cards

#### Quick Actions
- **Open latest booking** - Jump to their most recent appointment
- **View/Link WordPress user** - Connect to a WordPress account

---

## Viewing Reports

Track your business performance with analytics.

### Selecting Date Range

Choose from preset ranges or set custom dates:
- Last 7 Days
- Last 30 Days
- This Month
- Last Month
- This Year
- Custom Range

### Key Metrics

| Metric | What It Shows |
|--------|---------------|
| **Total Bookings** | Number of appointments in the period |
| **Deposits Collected** | Total deposit payments received |
| **Total Revenue** | All payments (deposits + balances) |
| **Completion Rate** | Percentage of completed appointments |
| **Cancellation Rate** | Percentage of cancelled appointments |

### Charts & Insights

- **Bookings by Status** - Visual breakdown of appointment statuses
- **Popular Services** - Your top 5 most-booked services
- **Bookings Over Time** - Daily trend line
- **Revenue Over Time** - Daily earnings bar chart
- **Busiest Days** - Which weekdays get the most bookings
- **Peak Hours** - Most popular booking times

### Exporting Data

Click **Export CSV** to download your booking data for the selected date range. Great for accounting or detailed analysis.

---

## Booking Statuses Explained

Understanding each status helps you manage bookings effectively:

| Status | Color | Meaning | Next Steps |
|--------|-------|---------|------------|
| **Pending** | Orange | Customer booked, awaiting your approval | Review and Confirm |
| **Confirmed** | Green | Appointment is approved and scheduled | Complete after service |
| **Completed** | Blue | Service has been delivered | No action needed |
| **Cancelled** | Red | Appointment was cancelled | Slot is now available |
| **No-show** | Gray | Customer didn't arrive | Mark if they miss appointment |

### Typical Booking Flow

```
Customer Books → Pending → You Confirm → Confirmed → Service Done → Completed
                    ↓                        ↓
                 Cancelled               No-show (if they don't arrive)
```

---

## Tips & Best Practices

### Daily Routine

1. **Morning Check**
   - Open Dashboard to see today's appointments
   - Review any pending bookings and confirm them
   - Note any special requests in customer notes

2. **Throughout the Day**
   - Mark appointments as "Completed" after each service
   - Mark "No-show" if a customer doesn't arrive

3. **End of Day**
   - Check tomorrow's schedule
   - Follow up on any pending bookings

### Managing Your Schedule

- **Block vacation dates early** - Add them as soon as you know
- **Set realistic limits** - Don't overbook yourself
- **Use buffers** - Allow time between appointments for prep/cleanup

### Customer Communication

- Check customer profiles before appointments to see their history
- Note any preferences or special requirements
- Review their past services to provide personalized service

### Staying Organized

- **Confirm bookings promptly** - Don't leave customers waiting
- **Update statuses immediately** - Keep your calendar accurate
- **Check reports weekly** - Understand your business trends

---

## Need Help?

If you encounter any issues or have questions:

1. Check this guide first for common tasks
2. Contact your system administrator for technical support
3. For feature requests or bugs, report to the site owner

---

*This guide is for GlowBook Booking Administrators. For full system configuration (services, payments, settings), contact your site administrator.*
