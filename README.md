# Chatterly

Chatterly is a **secure and scalable chat API application** designed to enable **real-time messaging**, **group management**, and **seamless communication**. Built with a **modular architecture**, it supports features like **one-to-one chat**, **group chats**, **real-time updates**, **user blocking/unblocking**, and **multi-device login**. The project is API-only, focusing on backend functionality.

---

## Features

### **1. Modular Architecture**
- **Separation of Concerns**: Organized into modules (e.g., User, Chat, Group) for better maintainability and scalability.
- **Reusable Components**: Each module is self-contained, making it easy to reuse or extend functionality.

### **2. User Authentication & Management**
- **User Sign-Up & Login**: Secure user authentication system.
- **Logout All Sessions**: Users can log out from all devices or specific sessions.
- **Multi-Device Login**: Supports seamless login across multiple devices.

### **3. Real-Time Messaging**
- **One-to-One Chat**: Users can send and receive messages in real-time.
- **Group Chats**: Create and manage groups with admins.
- **Real-Time Updates**: Powered by **WebSockets** for instant message delivery and updates.
- **Message Statuses**: Track message delivery and read statuses (e.g., pending, sent, read).

### **4. User Interaction**
- **Block/Unblock Users**: Users can block or unblock other users.
- **Search & Join Groups**: Users can search for and join groups.
- **Join/Leave Groups**: Users can join or leave groups at any time.

### **5. Group Management**
- **Group Creation**: Users can create and manage their own groups.
- **Group Admins**: Assign admins to manage groups.
- **Group Moderation**: Admins can manage members and group settings.
- **Group Ownership Transfer**: Group owner can change ownership to another member. 

### **6. SMS Integration**
- **SMS Notifications**: Send SMS for OTP and critical notifications like login alerts.

### **7. Comprehensive Testing**
- **PHPUnit**: Thoroughly tested for reliability and functionality.

---

## Technologies Used

### **Backend**
- **Framework**: [Laravel](https://laravel.com/)
- **Database**: [MySQL](https://www.mysql.com/)
- **Real-Time Communication**: [Pusher](https://pusher.com/)
- **SMS Integration**: SMSIR

### **Testing**
- **PHPUnit**: Comprehensive tests for application functionality.

---

## License
Chatterly is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.
