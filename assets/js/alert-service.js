class AlertService {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        if (!document.getElementById('alert-container')) {
            this.container = document.createElement('div');
            this.container.id = 'alert-container';
            this.container.className = 'fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-md';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('alert-container');
        }
    }

    colors = {
        success: '#22c55e',
        error: '#dc2626',
        warning: '#f59e0b',
        info: '#3b82f6'
    };

    icons = {
        success: '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>',
        error: '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>',
        warning: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
        info: '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118z"></path>'
    };

    show(type, title, message, options = {}) {
        const { autoDismiss = true, duration = 5000 } = options;
        const color = this.colors[type] || this.colors.info;
        
        const alert = document.createElement('div');
        alert.className = 'alert-item flex w-full h-24 overflow-hidden bg-white shadow-lg max-w-96 rounded-xl';
        alert.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" height="96" width="16">
                <path stroke-linecap="round" stroke-width="2" stroke="${color}" fill="${color}" d="M 8 0 
                    Q 4 4.8, 8 9.6 
                    T 8 19.2 
                    Q 4 24, 8 28.8 
                    T 8 38.4 
                    Q 4 43.2, 8 48 
                    T 8 57.6 
                    Q 4 62.4, 8 67.2 
                    T 8 76.8 
                    Q 4 81.6, 8 86.4 
                    T 8 96 
                    L 0 96 
                    L 0 0 
                    Z"></path>
            </svg>
            <div class="mx-2.5 overflow-hidden w-full">
                <p class="mt-1.5 text-xl font-bold leading-8 mr-3 overflow-hidden text-ellipsis whitespace-nowrap" style="color: ${color};">${title}</p>
                <p class="overflow-hidden leading-5 break-all text-zinc-400 max-h-10">${message}</p>
            </div>
            <button class="w-16 cursor-pointer focus:outline-none alert-close">
                <svg class="w-7 h-7 mx-auto" fill="none" stroke="${color}" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    ${this.icons[type] || this.icons.info}
                </svg>
            </button>
        `;

        this.container.appendChild(alert);

        const closeBtn = alert.querySelector('.alert-close');
        closeBtn.addEventListener('click', () => this.dismiss(alert));

        if (autoDismiss && duration > 0) {
            setTimeout(() => this.dismiss(alert), duration);
        }

        return alert;
    }

    success(title, message, duration = 5000) {
        return this.show('success', title, message, { autoDismiss: true, duration });
    }

    error(title, message) {
        return this.show('error', title, message, { autoDismiss: false });
    }

    warning(title, message, duration = 7000) {
        return this.show('warning', title, message, { autoDismiss: true, duration });
    }

    info(title, message, duration = 5000) {
        return this.show('info', title, message, { autoDismiss: true, duration });
    }

    dismiss(alert) {
        alert.style.transition = 'opacity 0.3s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }

    clear() {
        this.container.innerHTML = '';
    }
}

const Alert = new AlertService();

function CRUDAlert(action, entity, success = true) {
    const messages = {
        create: { success: ['Created Successfully', `${entity} has been created successfully.`], error: ['Creation Failed', `Failed to create ${entity.toLowerCase()}. Please try again.`] },
        update: { success: ['Updated Successfully', `${entity} has been updated successfully.`], error: ['Update Failed', `Failed to update ${entity.toLowerCase()}. Please try again.`] },
        delete: { success: ['Deleted Successfully', `${entity} has been deleted successfully.`], error: ['Delete Failed', `Failed to delete ${entity.toLowerCase()}. Please try again.`] },
        save: { success: ['Saved Successfully', `${entity} has been saved successfully.`], error: ['Save Failed', `Failed to save ${entity.toLowerCase()}. Please try again.`] },
        submit: { success: ['Submitted Successfully', `${entity} has been submitted successfully.`], error: ['Submission Failed', `Failed to submit ${entity.toLowerCase()}. Please try again.`] }
    };
    
    const msg = messages[action]?.[success ? 'success' : 'error'];
    if (msg) {
        success ? Alert.success(msg[0], msg[1]) : Alert.error(msg[0], msg[1]);
    }
}
