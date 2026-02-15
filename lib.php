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
 * Adds the Smart Dashboard link to Moodle's navigation drawer (flat navigation).
 *
 * Only visible to users with the local/smartdashboard:view capability
 * (teachers, managers) or site admins.
 *
 * @param global_navigation $navigation The global navigation object.
 */
function local_smartdashboard_extend_navigation(global_navigation $navigation)
{
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $context = context_system::instance();

    // Only show to admins, managers, and teachers.
    if (!is_siteadmin() && !has_capability('local/smartdashboard:view', $context)) {
        return;
    }

    $url = new moodle_url('/local/smartdashboard/index.php');

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
}

/**
 * Injects the Smart Dashboard link into the primary navigation bar
 * for Moodle 4.x Boost themes using JavaScript.
 *
 * This is needed because Moodle 4.x does not provide a PHP API to add
 * items to the top primary navigation bar programmatically.
 *
 * @return string HTML/JS to inject into the page.
 */
function local_smartdashboard_before_standard_top_of_body_html()
{
    global $PAGE;

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $context = context_system::instance();

    if (!is_siteadmin() && !has_capability('local/smartdashboard:view', $context)) {
        return '';
    }

    $url = new moodle_url('/local/smartdashboard/index.php');
    $linktext = get_string('pluginname', 'local_smartdashboard');
    $currenturl = $PAGE->url->out(false);
    $dashboardurl = $url->out(false);

    // Check if we're currently on the dashboard page for active state.
    $isactive = (strpos($currenturl, '/local/smartdashboard/') !== false);
    $activeclass = $isactive ? ' active' : '';

    return '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Find the primary navigation list.
        var primaryNav = document.querySelector(".primary-navigation .moremenu ul[role=\"menubar\"]");
        if (!primaryNav) {
            primaryNav = document.querySelector("ul.nav.more-nav.navbar-nav");
        }
        if (!primaryNav) {
            primaryNav = document.querySelector(".primary-navigation ul");
        }
        if (primaryNav) {
            // Check if link already exists.
            var existingLink = primaryNav.querySelector("a[href*=\"/local/smartdashboard/\"]");
            if (!existingLink) {
                var li = document.createElement("li");
                li.className = "nav-item";
                li.setAttribute("data-key", "local_smartdashboard");
                li.setAttribute("role", "none");

                var a = document.createElement("a");
                a.className = "nav-link' . $activeclass . '";
                a.setAttribute("role", "menuitem");
                a.href = "' . $dashboardurl . '";
                a.textContent = "' . $linktext . '";
                a.setAttribute("tabindex", "-1");

                li.appendChild(a);

                // Insert before the "More" menu if it exists, otherwise append.
                var moreMenu = primaryNav.querySelector("li.nav-item.dropdown");
                if (moreMenu) {
                    primaryNav.insertBefore(li, moreMenu);
                } else {
                    primaryNav.appendChild(li);
                }
            }
        }
    });
    </script>';
}
