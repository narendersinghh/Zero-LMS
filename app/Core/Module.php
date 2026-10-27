<?php
namespace App\Core;

/** Base class for feature modules. */
abstract class Module {
    protected string $path;

    public function __construct(string $path) {
        $this->path = $path;}

    abstract public function register(App $app): void;

    /** Add an item to the admin sidebar. */
    protected function adminNav(string $label, string $url, string $icon = 'package', string $perm = 'admin.access', int $order = 50): void {
        Hooks::on('admin.nav', function (array $items) use ($label, $url, $icon, $perm, $order) {
            $items[] = ['label' => $label, 'url' => $url, 'icon' => $icon, 'perm' => $perm, 'order' => $order];
            return $items;});}

    /** Add an item to the student navigation. */
    protected function studentNav(string $label, string $url, string $icon = 'package', int $order = 50): void {
        Hooks::on('student.nav', function (array $items) use ($label, $url, $icon, $order) {
            $items[] = ['label' => $label, 'url' => $url, 'icon' => $icon, 'order' => $order];
            return $items;});}

    /** Grant extra permissions to roles. */
    protected function permissions(array $map): void {
        Hooks::on('auth.permissions', function (array $p) use ($map) {
            foreach ($map as $role => $perms) {
                $p[$role] = array_values(array_unique(array_merge($p[$role] ?? [], $perms)));}
            return $p;});}}
