<?php

declare(strict_types=1);

namespace HealthSafety\Services;

use PDO;

class RoleHierarchyService
{
    private PDO $pdo;
    private array $roleHierarchy = [
        'super_admin' => 1,
        'admin' => 2,
        'senior_inspector' => 3,
        'inspector' => 4,
        'establishment_owner' => 5,
        'public' => 6
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if user has higher or equal role level
     */
    public function hasRoleLevel(string $userRole, string $requiredRole): bool
    {
        $userLevel = $this->roleHierarchy[$userRole] ?? 999;
        $requiredLevel = $this->roleHierarchy[$requiredRole] ?? 999;
        
        // Lower number = higher privilege
        return $userLevel <= $requiredLevel;
    }

    /**
     * Check if user can perform action on target user
     */
    public function canManageUser(string $actorRole, string $targetRole): bool
    {
        $actorLevel = $this->roleHierarchy[$actorRole] ?? 999;
        $targetLevel = $this->roleHierarchy[$targetRole] ?? 999;
        
        // Can only manage users with lower privilege
        return $actorLevel < $targetLevel;
    }

    /**
     * Get all roles that current user can assign
     */
    public function getAssignableRoles(string $userRole): array
    {
        $userLevel = $this->roleHierarchy[$userRole] ?? 999;
        
        $assignable = [];
        foreach ($this->roleHierarchy as $role => $level) {
            if ($level > $userLevel) {
                $assignable[] = $role;
            }
        }
        
        return $assignable;
    }

    /**
     * Get inherited permissions from role hierarchy
     */
    public function getInheritedPermissions(int $roleId): array
    {
        // Get role hierarchy level
        $stmt = $this->pdo->prepare('
            SELECT role_name, hierarchy_level 
            FROM roles 
            WHERE role_id = :role_id
        ');
        $stmt->execute(['role_id' => $roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$role) {
            return [];
        }
        
        // Get all permissions for this role and lower roles
        $stmt = $this->pdo->prepare('
            SELECT DISTINCT p.permission_key, p.module, p.action, p.description
            FROM roles r
            JOIN role_permissions rp ON r.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE r.hierarchy_level >= :level
            ORDER BY p.module, p.action
        ');
        $stmt->execute(['level' => $role['hierarchy_level']]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user owns resource
     */
    public function ownsResource(string $resourceType, int $resourceId, ?int $userId): bool
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
                $stmt = $this->pdo->prepare('
                    SELECT COUNT(*) 
                    FROM inspections i
                    JOIN establishments e ON i.establishment_id = e.establishment_id
                    WHERE i.inspection_id = :resource_id 
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
}
