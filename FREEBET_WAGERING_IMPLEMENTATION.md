# Freebet Wagering Implementation Plan

This document tracks the progress of implementing a wagering system for the Freebet wallet. Users must play through a specific amount of money in the Freebet wallet before it is converted to real withdrawable cash.

## 🟢 Status: Complete

---

## 🛠 1. Database & Configuration
Set up the tracking columns and admin-controlled rules.
- [x] **Table: `wallets`**
  - Add `wagering_remaining` (decimal): Tracks how much more the user needs to bet.
- [x] **Table: `settings`**
  - Add `freebet_wagering_multiplier` (e.g., 10 for 10x).
  - Add `freebet_min_multiplier` (e.g., 1.50): Only bets cashed out above this count towards wagering.

## ⚙️ 2. Awarding the Bonus (Registration)
Update the signup logic to set the initial target.
- [x] **Registration Hook:**
  - When awarding the `signup_freebet_bonus`, calculate `wagering_remaining = bonus * multiplier`.
  - Save the initial requirement to the user's wallet.

## 🕹 3. The Wagering Engine (Game Logic)
Deduct from the requirement as the user plays.
- [x] **Bet Placement Logic:**
  - If `wallet_type` is `freebet`, track the bet.
- [x] **Win/Loss Processing:**
  - If the bet meets the `freebet_min_multiplier` rule, subtract the bet amount from `wagering_remaining`.
- [x] **Auto-Conversion:**
  - If `wagering_remaining` reaches 0, move the entire `freebet_amount` balance to the real `amount` wallet.
  - Create a transaction log for the conversion.

## 🎨 4. Frontend Feedback (User UI)
Let the user know their progress so they stay engaged.
- [x] **Wallet Dropdown:** Added a sleek wagering progress bar in the game header.
- [x] **Real-time Updates:** Progress bar updates automatically every 3 seconds (via wallet auto-refresh).

---

## 📈 Progress Tracker
- [x] **Phase 1: Database Setup**
- [x] **Phase 2: Admin Rules Configuration**
- [x] **Phase 3: Registration Integration**
- [x] **Phase 4: Game Logic Integration**
- [x] **Phase 5: User UI Progress Display**

---
**Last Updated:** March 28, 2026
