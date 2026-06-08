<?php

class Alert {
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';
    const INFO = 'info';

    private $messages = [];

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->messages = $_SESSION['alerts'] ?? [];
        unset($_SESSION['alerts']);
    }

    public function add($type, $title, $message, $autoDismiss = true, $duration = 5000) {
        $this->messages[] = [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'autoDismiss' => $autoDismiss,
            'duration' => $duration
        ];
        $_SESSION['alerts'] = $this->messages;
    }

    public function success($title, $message, $duration = 5000) {
        $this->add(self::SUCCESS, $title, $message, true, $duration);
    }

    public function error($title, $message, $duration = 0) {
        $this->add(self::ERROR, $title, $message, false, $duration);
    }

    public function warning($title, $message, $duration = 7000) {
        $this->add(self::WARNING, $title, $message, true, $duration);
    }

    public function info($title, $message, $duration = 5000) {
        $this->add(self::INFO, $title, $message, true, $duration);
    }

    public function getMessages() {
        return $this->messages;
    }

    public function hasMessages() {
        return !empty($this->messages);
    }

    public function getColor($type) {
        $colors = [
            self::SUCCESS => '#22c55e',
            self::ERROR => '#dc2626',
            self::WARNING => '#f59e0b',
            self::INFO => '#3b82f6'
        ];
        return $colors[$type] ?? '#6b7280';
    }

    public function getIcon($type) {
        $icons = [
            self::SUCCESS => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>',
            self::ERROR => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>',
            self::WARNING => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
            self::INFO => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118z"></path>'
        ];
        return $icons[$type] ?? '';
    }

    public function render() {
        if (!$this->hasMessages()) return '';
        
        $output = '<div id="alert-container" class="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-md">';
        
        foreach ($this->messages as $index => $alert) {
            $color = $this->getColor($alert['type']);
            $autoDismiss = $alert['autoDismiss'] ? 'data-autodismiss="' . $alert['duration'] . '"' : '';
            $output .= "
            <div class='alert-item flex w-full h-24 overflow-hidden bg-white shadow-lg max-w-96 rounded-xl' {$autoDismiss} data-index='{$index}'>
                <svg xmlns='http://www.w3.org/2000/svg' height='96' width='16'>
                    <path stroke-linecap='round' stroke-width='2' stroke='{$color}' fill='{$color}' d='M 8 0 
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
                        Z'></path>
                </svg>
                <div class='mx-2.5 overflow-hidden w-full'>
                    <p class='mt-1.5 text-xl font-bold' style='color: {$color};' class='leading-8 mr-3 overflow-hidden text-ellipsis whitespace-nowrap'>
                        {$alert['title']}
                    </p>
                    <p class='overflow-hidden leading-5 break-all text-zinc-400 max-h-10'>
                        " . nl2br(htmlspecialchars($alert['message'])) . "
                    </p>
                </div>
                <button class='w-16 cursor-pointer focus:outline-none alert-close' data-index='{$index}'>
                    <svg class='w-7 h-7 mx-auto' fill='none' stroke='{$color}' stroke-width='2' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>
                        {$this->getIcon($alert['type'])}
                    </svg>
                </button>
            </div>";
        }
        
        $output .= '</div>';
        $output .= $this->renderScript();
        return $output;
    }

    private function renderScript() {
        return "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-item');
            alerts.forEach(function(alert) {
                const autoDismiss = alert.getAttribute('data-autodismiss');
                if (autoDismiss && parseInt(autoDismiss) > 0) {
                    setTimeout(function() {
                        alert.style.transition = 'opacity 0.3s ease';
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }, parseInt(autoDismiss));
                }
            });
            
            document.querySelectorAll('.alert-close').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    const alert = document.querySelector('.alert-item[data-index=\"' + index + '\"]');
                    if (alert) {
                        alert.style.transition = 'opacity 0.3s ease';
                        alert.style.opacity = '0';
                        setTimeout(() => alert.remove(), 300);
                    }
                });
            });
        });
        </script>";
    }
}

function alert() {
    return new Alert();
}

function alertSuccess($title, $message, $duration = 5000) {
    $alert = new Alert();
    $alert->success($title, $message, $duration);
}

function alertError($title, $message) {
    $alert = new Alert();
    $alert->error($title, $message);
}

function alertWarning($title, $message, $duration = 7000) {
    $alert = new Alert();
    $alert->warning($title, $message, $duration);
}

function alertInfo($title, $message, $duration = 5000) {
    $alert = new Alert();
    $alert->info($title, $message, $duration);
}
?>