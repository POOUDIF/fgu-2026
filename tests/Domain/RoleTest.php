<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function testHomeFor(): void
    {
        $this->assertSame('admin', Role::homeFor(Role::SUPER_ADMIN));
        $this->assertSame('admin', Role::homeFor(Role::REGIONAL_ADMIN));
        $this->assertSame('desa', Role::homeFor(Role::VILLAGE_ADMIN));
        $this->assertSame('admin-lomba', Role::homeFor(Role::COMPETITION_ADMIN));
        $this->assertSame('juri', Role::homeFor(Role::JUDGE));
    }

    public function testRegionalAdminCannotManageAdmins(): void
    {
        $this->assertFalse(Role::canManage(Role::REGIONAL_ADMIN, Role::SUPER_ADMIN));
        $this->assertFalse(Role::canManage(Role::REGIONAL_ADMIN, Role::REGIONAL_ADMIN));
        $this->assertTrue(Role::canManage(Role::REGIONAL_ADMIN, Role::JUDGE));
        $this->assertTrue(Role::canManage(Role::REGIONAL_ADMIN, Role::COMPETITION_ADMIN));
        $this->assertTrue(Role::canManage(Role::REGIONAL_ADMIN, Role::VILLAGE_ADMIN));
    }

    public function testSuperAdminManagesEveryone(): void
    {
        foreach (Role::ALL as $r) {
            $this->assertTrue(Role::canManage(Role::SUPER_ADMIN, $r));
        }
    }

    public function testNonAdminsManageNobody(): void
    {
        $this->assertFalse(Role::canManage(Role::JUDGE, Role::JUDGE));
        $this->assertFalse(Role::canManage(Role::VILLAGE_ADMIN, Role::JUDGE));
    }

    public function testAssignableRoles(): void
    {
        $this->assertSame(Role::ALL, Role::assignableBy(Role::SUPER_ADMIN));
        $this->assertSame([Role::VILLAGE_ADMIN, Role::JUDGE, Role::COMPETITION_ADMIN], Role::assignableBy(Role::REGIONAL_ADMIN));
        $this->assertSame([], Role::assignableBy(Role::JUDGE));
    }
}
