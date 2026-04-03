# P2P Withdrawal Implementation Plan

This document tracks the progress of adding an intuitive P2P withdrawal system to the Aviator project. The system will simulate a "Peer-to-Peer" matching experience while pulling verified numbers from an Admin-managed pool.

## 🟢 Status: Planning

---

## 🛠 1. Database Schema
Create the necessary tables to manage peer numbers and P2P-specific transactions.
- [ ] **Table: `p2p_peers`**
  - `id`, `name`, `phone`, `status` (online/offline), `min_limit`, `max_limit`, `success_rate`, `avg_time`.
- [ ] **Table: `p2p_withdrawals`**
  - `id`, `user_id`, `peer_id`, `amount`, `reference`, `status` (searching, matched, completed, failed), `matched_at`, `completed_at`.

## 🛡 2. Admin Management (Backend & UI)
Enable administrators to manage the "Peer Pool" and monitor P2P activity.
- [ ] **Peer CRUD:** Add, Edit, Delete, and Toggle "Online" status for peer numbers.
- [ ] **P2P Monitoring:** A dashboard view to see active "matches" and their current status.
- [ ] **Manual Approval:** Option for admin to manually "Complete" or "Cancel" a P2P request.

## ⚙️ 3. Backend Logic (Laravel)
The engine that handles matching and wallet security.
- [ ] **API: `initiateP2PSearch`**
  - Deduct wallet balance (move to "pending").
  - Create a `p2p_withdrawal` record with `status: searching`.
- [ ] **Matching Algorithm:** Logic to pick a random "Online" peer whose limits match the user's withdrawal amount.
- [ ] **API: `getMatchDetails`**
  - Returns the peer info after a simulated delay (for UX).

## 🎨 4. Frontend Experience (User Interface)
Focus on making the process intuitive and visually engaging.
- [ ] **Withdrawal Tab:** Add a "P2P Withdrawal" option alongside M-Pesa.
- [ ] **The "Radar" Animation:** 
  - [ ] CSS-based pulse/scanning animation.
  - [ ] Rotating status messages ("Scanning nodes...", "Verifying peer liquidity...").
- [ ] **Matched Peer Card:** 
  - [ ] Show peer profile (Name, Phone, Success Rate).
  - [ ] "Copy Phone Number" functionality.
  - [ ] Countdown timer for the transaction window.
- [ ] **Sound Effects:** Subtle "ping" when match is found (optional/suggested).

## 🧪 5. Testing & Validation
- [ ] Verify wallet deduction and refund logic (if cancelled).
- [ ] Test matching logic with multiple peers (limits/status).
- [ ] Ensure mobile responsiveness of the Radar UI.
- [ ] End-to-end flow from User Request -> Admin Approval -> Completion.

---

## 📈 Progress Tracker
- [x] **Phase 1: Database Setup**
- [x] **Phase 2: Admin Peer Management**
- [x] **Phase 3: Backend API Integration**
- [x] **Phase 4: Interactive Frontend UI**
- [ ] **Phase 5: Final Polish & Testing**

---
**Last Updated:** March 28, 2026
