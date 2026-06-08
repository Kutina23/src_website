<?php
class Downloads {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function getAllActive(): array {
        // Fallback: use id DESC instead of created_at DESC if column missing
        $sql = "SELECT d.*, dc.name as category_name, dc.icon as category_icon
                FROM downloads d
                JOIN download_categories dc ON d.category_id = dc.id
                WHERE d.is_active = 1
                ORDER BY dc.display_order ASC, d.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getByCategory($categoryId): array {
        $sql = "SELECT * FROM downloads 
                WHERE category_id = ? AND is_active = 1 
                ORDER BY id DESC";
        return $this->db->fetchAll($sql, [$categoryId]);
    }

    public function getCategories(): array {
        $sql = "SELECT * FROM download_categories 
                ORDER BY display_order ASC, name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById(int $id): ?array {
        return $this->db->fetch(
            "SELECT d.*, dc.name as category_name, dc.icon as category_icon 
             FROM downloads d 
             JOIN download_categories dc ON d.category_id = dc.id 
             WHERE d.id = ?",
            [$id]
        );
    }

    public function incrementDownload(int $id): bool {
        return $this->db->execute(
            "UPDATE downloads SET download_count = download_count + 1 WHERE id = ?",
            [$id]
        );
    }

    public function getFileTypeIcon(string $fileType): string {
        return match ($fileType) {
            "pdf" => "bi-file-pdf",
            "doc", "docx" => "bi-file-word",
            "xls", "xlsx" => "bi-file-excel",
            "ppt", "pptx" => "bi-file-ppt",
            "txt" => "bi-file-text",
            "zip", "rar" => "bi-file-zip",
            "jpg", "jpeg", "png", "gif" => "bi-file-image",
            default => "bi-file-earmark",
        };
    }

    public function getFileSize(int $bytes): string {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . " GB";
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . " MB";
        if ($bytes >= 1024) return round($bytes / 1024, 2) . " KB";
        return $bytes . " B";
    }
}
?>
