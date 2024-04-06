<?php
namespace RestrictUserAccess\Membership;

use RestrictUserAccess\Hook\HookService;
use RestrictUserAccess\Hook\HookSubscriberInterface;

/**
 * Class QueryFilters
 *
 * @author Joachim Jensen <joachim@dev.institute>
 * @license https://www.gnu.org/licenses/gpl-3.0.html
 */
class QueryFilters implements HookSubscriberInterface
{
    public function subscribe(HookService $service)
    {
        $service->add_action(
            'parse_comment_query',
            [$this, 'exclude_comment_type']
        );
        $service->add_filter(
            'comments_clauses',
            [$this, 'intercept_membership_query_clauses'],
            1,
            2
        );
    }

    /**
     * Ensure plugins that indiscriminately manipulate
     * comment query clauses don't affect membership queries
     * @param array $clauses
     * @param \WP_Comment_Query $query
     * @return array
     */
    public function intercept_membership_query_clauses($clauses, $query)
    {
        if (!$this->is_membership_query($query)) {
            return $clauses;
        }

        $this->ensure_final_clauses($clauses);
        return $clauses;
    }

    /**
     * @param \WP_Comment_Query $query
     * @return void
     */
    public function exclude_comment_type($query)
    {
        if ($this->is_membership_query($query)) {
            return;
        }

        $query->query_vars['type__not_in'] = (array) $query->query_vars['type__not_in'];
        $query->query_vars['type__not_in'][] = \RUA_User_Level::ENTITY_TYPE;
    }

    /**
     * @param \WP_Query|\WP_Comment_Query  $query
     * @return bool
     */
    private function is_membership_query($query)
    {
        return in_array(\RUA_User_Level::ENTITY_TYPE, (array) $query->query_vars['type'])
            || in_array(\RUA_User_Level::ENTITY_TYPE, (array) $query->query_vars['type__in']);
    }

    /**
     * @param array $clauses_final
     * @return void
     */
    private function ensure_final_clauses($clauses_final)
    {
        $priority = 999;
        $restore_clauses = function ($clauses, $query) use ($clauses_final, $priority, &$restore_clauses) {
            remove_filter('comments_clauses', $restore_clauses, $priority);
            return $this->is_membership_query($query) ? $clauses_final : $clauses;
        };
        add_filter('comments_clauses', $restore_clauses, $priority, 2);
    }
}
