<?php
namespace App\Core;

class Paginator {
    public int $total;
    public int $perPage;
    public int $page;
    public int $pages;

    public function __construct(int $total, int $perPage = 25, ?int $page = null) {
        $this->total = $total;
        $this->perPage = max(1, $perPage);
        $this->pages = max(1, (int)ceil($total / $this->perPage));
        $this->page = min($this->pages, max(1, $page ?? (int)($_GET['page'] ?? 1)));}

    public function offset(): int {
        return ($this->page - 1) * $this->perPage;}

    public function limitSql(): string {
        return ' LIMIT ' . $this->perPage . ' OFFSET ' . $this->offset();}

    public function links(): string {
        if ($this->pages <= 1) {
            return '';}
        $q = $_GET;
        $html = '<nav class="pagination" aria-label="Pagination"><span class="muted">Page ' . $this->page . ' of ' . $this->pages . ' · ' . number_format($this->total) . ' total</span><div class="pages">';
        $from = max(1, $this->page - 2);
        $to = min($this->pages, $this->page + 2);
        $mk = function ($p, $label, $active = false) use ($q) {
            $q['page'] = $p;
            return '<a class="page' . ($active ? ' active' : '') . '" href="?' . e(http_build_query($q)) . '">' . $label . '</a>';};
        if ($this->page > 1) {
            $html .= $mk($this->page - 1, '‹');}
        for ($p = $from; $p <= $to; $p++) {
            $html .= $mk($p, (string)$p, $p === $this->page);}
        if ($this->page < $this->pages) {
            $html .= $mk($this->page + 1, '›');}
        return $html . '</div></nav>';}}
