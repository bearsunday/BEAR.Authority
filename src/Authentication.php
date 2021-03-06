<?php
namespace Ryo88c\Authority;

use Ray\Di\Di\Named;

class Authentication implements AuthenticationInterface
{
    private $config;

    /**
     * Authentication constructor.
     *
     * @param array $config
     *
     * @Named("config=authentication_config")
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function authenticate(Audience $audience, Auth $annotation) : bool
    {
        $condition = $this->extractAuthCondition($annotation);
        if (empty($condition)) {
            return true;
        }
        $evaluated = in_array($audience->role, $condition['roles'], true);

        return $condition['comparison'] === 'allow' ? $evaluated : ! $evaluated;
    }

    private function extractAuthCondition(Auth $annotation)
    {
        if (! empty($annotation->allow)) {
            if (! empty($annotation->deny)) {
                throw new \InvalidArgumentException('Allow and deny can not coexistence.');
            }

            return ['roles' => $this->extractAuthAnnotation($annotation, 'allow'), 'comparison' => 'allow'];
        } elseif (! empty($annotation->deny)) {
            return ['roles' => $this->extractAuthAnnotation($annotation, 'deny'), 'comparison' => 'deny'];
        }
    }

    private function extractAuthAnnotation(Auth $annotation, $permission) : array
    {
        $roles = explode(',', $annotation->{$permission});
        foreach ($roles as &$role) {
            $role = strtolower(trim($role));
            if (! in_array($role, $this->config['definedRoles'], true)) {
                throw new \InvalidArgumentException(sprintf('%s is undefined as a role.', $role));
            }
        }

        return $roles;
    }
}
