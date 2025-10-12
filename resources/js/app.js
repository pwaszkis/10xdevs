import './bootstrap';
import Alpine from 'alpinejs';

// Initialize Alpine.js
window.Alpine = Alpine;

// Register fallback Alpine components for WireUI (not used but vendor might reference them)
Alpine.data('wireui_dropdown', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    },
    // positionable is a sub-object used by wireui_dropdown
    positionable: {
        state: false,
        open: false,
        openIfClosed() {
            this.state = true;
            this.open = true;
        },
        close() {
            this.state = false;
            this.open = false;
        },
        toggle() {
            this.state = !this.state;
            this.open = !this.open;
        }
    }
}));

Alpine.data('positionable', () => ({
    state: false,
    open: false,
    openIfClosed() {
        this.state = true;
        this.open = true;
    },
    close() {
        this.state = false;
        this.open = false;
    },
    toggle() {
        this.state = !this.state;
        this.open = !this.open;
    }
}));

Alpine.start();

// Optional: Add global Alpine components or directives here
// Example:
// Alpine.data('counter', () => ({
//     count: 0,
//     increment() {
//         this.count++
//     }
// }))
