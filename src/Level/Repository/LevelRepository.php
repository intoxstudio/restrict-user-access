<?php

namespace RestrictUserAccess\Level\Repository;

use RestrictUserAccess\Level\PostType;
use RestrictUserAccess\Repository\AbstractRepository;
use RUA_Level;

/**
 * Class LevelRepository
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class LevelRepository extends AbstractRepository implements LevelRepositoryInterface
{
    public function find($id)
    {
        $wp_entity = \WP_Post::get_instance($id);
        if (!($wp_entity instanceof \WP_Post)) {
            return null;
        }
        return new RUA_Level($wp_entity);
    }

    /**
     * @param $args
     * @return \WP_Query
     */
    protected function query($args)
    {
        $args['post_type'] = PostType::NAME;
        return new \WP_Query($args);
    }
}
