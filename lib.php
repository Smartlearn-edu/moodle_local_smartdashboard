<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library functions for Smart Dashboard.
 *
 * @package     local_smartdashboard
 * @copyright   2025 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds the Smart Dashboard link to Moodle's navigation.
 *
 * Adds both a navigation drawer entry and injects JS to add
 * a link to the primary navigation bar in Moodle 4.x Boost themes.
 *
 * Only visible to users with the local/smartdashboard:view capability
 * (teachers, managers) or site admins.
 *
 * @param global_navigation $navigation The global navigation object.
 */
function local_smartdashboard_extend_navigation(global_navigation $navigation)
{
    global $PAGE;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $context = context_system::instance();

    // Only show to admins, managers, and teachers.
    if (!is_siteadmin() && !has_capability('local/smartdashboard:view', $context)) {
        return;
    }

    $url = new moodle_url('/local/smartdashboard/index.php');

    // 1. Add to navigation drawer (flat navigation).
    $node = $navigation->add(
        get_string('pluginname', 'local_smartdashboard'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_smartdashboard',
        new pix_icon('i/report', '')
    );

    if ($node) {
        $node->showinflatnavigation = true;
    }

    // 2. Inject JS to add link to the primary navigation bar (Moodle 4.x Boost).
    $dashboardurl = $url->out(false);
    $linktext = get_string('pluginname', 'local_smartdashboard');

    $jscode = '
        document.addEventListener("DOMContentLoaded", function() {
            var primaryNav = document.querySelector(".primary-navigation .moremenu ul[role=\'menubar\']");
            if (!primaryNav) {
                primaryNav = document.querySelector("ul.nav.more-nav.navbar-nav");
            }
            if (!primaryNav) {
                primaryNav = document.querySelector(".primary-navigation ul");
            }
            if (primaryNav) {
                var existingLink = primaryNav.querySelector("a[href*=\'/local/smartdashboard/\']");
                if (!existingLink) {
                    var li = document.createElement("li");
                    li.className = "nav-item";
                    li.setAttribute("data-key", "local_smartdashboard");
                    li.setAttribute("role", "none");

                    var a = document.createElement("a");
                    a.className = "nav-link";
                    a.setAttribute("role", "menuitem");
                    a.href = "' . $dashboardurl . '";
                    a.textContent = "' . $linktext . '";
                    a.setAttribute("tabindex", "-1");

                    li.appendChild(a);

                    var moreMenu = primaryNav.querySelector("li.nav-item.dropdown");
                    if (moreMenu) {
                        primaryNav.insertBefore(li, moreMenu);
                    } else {
                        primaryNav.appendChild(li);
                    }
                }
            }
        });
    ';

    try {
        $PAGE->requires->js_init_code($jscode);
    } catch (Exception $e) {
        // Silently fail if $PAGE is not ready.
    }
}
