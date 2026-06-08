class LoaderService {
    constructor() {
        this.overlay = null;
        this.init();
    }

    init() {
        this.overlay = document.createElement('div');
        this.overlay.id = 'global-loader-overlay';
        this.overlay.className = 'spinner-overlay';
        this.overlay.innerHTML = '<div class="spinner"><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div></div>';
        document.body.appendChild(this.overlay);
    }

    show() {
        if (this.overlay) {
            this.overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    hide() {
        if (this.overlay) {
            this.overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    toggle() {
        if (this.overlay.classList.contains('active')) {
            this.hide();
        } else {
            this.show();
        }
    }
}

const Loader = new LoaderService();

function showLoader() {
    Loader.show();
}

function hideLoader() {
    Loader.hide();
}

function toggleLoader() {
    Loader.toggle();
}

function withLoader(promise, options = {}) {
    const { showOverlay = true, button = null } = options;
    
    if (showOverlay) Loader.show();
    if (button) {
        button.classList.add('btn-loading');
        const originalContent = button.innerHTML;
        button.setAttribute('data-original-content', originalContent);
    }
    
    return promise
        .then(result => {
            if (button) {
                button.classList.remove('btn-loading');
                button.innerHTML = button.getAttribute('data-original-content');
            }
            if (showOverlay) Loader.hide();
            return result;
        })
        .catch(error => {
            if (button) {
                button.classList.remove('btn-loading');
                button.innerHTML = button.getAttribute('data-original-content');
            }
            if (showOverlay) Loader.hide();
            throw error;
        });
}

function showButtonLoader(button) {
    button.classList.add('btn-loading');
    button.setAttribute('data-original-content', button.innerHTML);
}

function hideButtonLoader(button) {
    button.classList.remove('btn-loading');
    button.innerHTML = button.getAttribute('data-original-content');
}

document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href]:not([target="_blank"]):not([href^="#"]):not([href^="javascript:"])');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            if (link.getAttribute('href') && !link.getAttribute('href').startsWith('#')) {
                Loader.show();
                setTimeout(() => Loader.hide(), 5000);
            }
        });
    });

    window.addEventListener('beforeunload', function() {
        Loader.show();
    });
    
    setTimeout(() => Loader.hide(), 5000);
});