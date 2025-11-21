<?php

declare(strict_types=1);

namespace HealthSafety\Middleware;

use HealthSafety\Utils\Response;
use HealthSafety\Utils\Logger;

/**
 * Role-Based Access Control Middleware
 * Health & Safety Inspections System
 */
class RoleMiddleware
{
    private \PDO $pdo;
    
    private array $roleHierarchy = [
        'super_admin' => 1,
        'admin' => 2,
        'senior_inspector' => 3,
        'inspector' => 4,
        'establishment_owner' => 5,
        'public' => 6
    ];
    
    public function __construct()
    {
        $this->pdo = \Database::getConnection();
    }
    
    /**
     * Require specific permission
     */
    public function requirePermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            Logger::security('Permission denied', 'WARNING', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'permission' => $permission,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            Response::forbidden('You do not have permission to perform this action');
        }
    }
    
    /**
     * Require minimum role level
     */
    public function requireRole(string $minRole): void
    {
        $userRole = $_SESSION['role'] ?? 'public';
        
        if (!$this->hasRoleLevel($userRole, $minRole)) {
            Response::forbidden("This action requires $minRole role or higher");
        }
    }
    
    /**
     * Require any of the specified roles
     */
    public function requireAnyRole(array $roles): void
    {
        $userRole = $_SESSION['role'] ?? 'public';
        
        if (!in_array($userRole, $roles)) {
            Response::forbidden('You do not have the required role to perform this action');
        }
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            return false;
        }
        
        // Super admin has all permissions
        if (($_SESSION['role'] ?? '') === 'super_admin') {
            return true;
        }
        
        // Check if permission exists in session
        $permissions = $_SESSION['permissions'] ?? [];
        
        return in_array($permission, $permissions);
    }
    
    /**
     * Check if user has role level
     */
    private function hasRoleLevel(string $userRole, string $requiredRole): bool
    {
        $userLevel = $this->roleHierarchy[$userRole] ?? 999;
        $requiredLevel = $this->roleHierarchy[$requiredRole] ?? 999;
        
        // Lower number = higher privilege
        return $userLevel <= $requiredLevel;
    }
    
    /**
     * Check if user can manage target user
     */
    public function canManageUser(int $targetUserId): void
    {
        $actorRole = $_SESSION['role'] ?? 'public';
        
        // Get target user's role
        $stmt = $this->pdo->prepare('
            SELECT r.role_name 
            FROM users u
            JOIN user_roles ur ON u.user_id = ur.user_id
            JOIN roles r ON ur.role_id = r.role_id
            WHERE u.user_id = :user_id
            LIMIT 1
        ');
        
        $stmt->execute(['user_id' => $targetUserId]);
        $targetRole = $stmt->fetchColumn();
        
        if (!$targetRole) {
            Response::notFound('User not found');
        }
        
        // Check if actor can manage target
        $actorLevel = $this->roleHierarchy[$actorRole] ?? 999;
        $targetLevel = $this->roleHierarchy[$targetRole] ?? 999;
        
        // Can only manage users with lower privilege (higher number)
        if ($actorLevel >= $targetLevel) {
            Response::forbidden('You cannot manage users with equal or higher privilege');
        }
    }
    
    /**
     * Check if user owns the resource
     */
    public function requireOwnership(string $resourceType, int $resourceId): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $userRole = $_SESSION['role'] ?? 'public';
        
        // Admins and inspectors can access anything
        if (in_array($userRole, ['super_admin', 'admin', 'senior_inspector'])) {
            return;
        }
        
        // Check ownership
        if (!$this->ownsResource($resourceType, $resourceId, $userId)) {
            Response::forbidden('You can only access your own resources');
        }
    }
    
    /**
     * Check if user owns resource
     */
    private function ownsResource(string $resourceType, int $resourceId, ?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        
        switch ($resourceType) {
            case 'establishment':
                $stmt = $this->pdo->prepare('
                    SELECT COUNT(*) FROM establishments 
                    WHERE establishment_id = :resource_id 
                    AND owner_user_id = :user_id
                ');
                break;
                
            case 'inspection':
                // Check if inspection is for user's establishment or user is the inspector
                $stmt = $this->pdo->prepare('
                    SELECT COUNT(*) 
                    FROM inspections i
                    LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
                    LEFT JOIN inspectors insp ON i.inspector_id = insp.inspector_id
                    WHERE i.inspection_id = :resource_id 
                    AND (e.owner_user_id = :user_id OR insp.user_id = :user_id)
                ');
                break;
                
            case 'violation':
                // Check if violation is for user's establishment
                $stmt = $this->pdo->prepare('
                    SELECT COUNT(*) 
                    FROM violations v
                    JOIN establishments e ON v.establishment_id = e.establishment_id
                    WHERE v.violation_id = :resource_id 
                    AND e.owner_user_id = :user_id
                ');
                break;
                
            case 'certificate':
                // Check if certificate belongs to user's establishment
                $stmt = $this->pdo->prepare('
                    SELECT COUNT(*) 
                    FROM certificates c
                    JOIN establishments e ON c.establishment_id = e.establishment_id
                    WHERE c.certificate_id = :resource_id 
                    AND e.owner_user_id = :user_id
                ');
                break;
                
            default:
                return false;
        }
        
        $stmt->execute([
            'resource_id' => $resourceId,
            'user_id' => $userId
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get all permissions for current user
     */
    public static function getUserPermissions(): array
    {
        return $_SESSION['permissions'] ?? [];
    }
}
