<?php

/**
 * Class pw_new_user_approve_admin_approve
 * Admin must approve all new users
 */

class pw_new_user_approve_admin_approve {

    var $_admin_page = 'new-user-approve-admin';

    /**
     * The only instance of pw_new_user_approve_admin_approve.
     *
     * @var pw_new_user_approve_admin_approve
     */
    private static $instance;

    /**
     * Returns the main instance.
     *
     * @return pw_new_user_approve_admin_approve
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new pw_new_user_approve_admin_approve();
        }
        return self::$instance;
    }

    private function __construct() {
        // Actions
        add_action( 'admin_menu', array( $this, 'admin_menu_link' ) );
        add_action( 'init',	array( $this, 'process_input' ) );
    }

    /**
     * Add the new menu item to the users portion of the admin menu
     */
    function admin_menu_link() {
        $show_admin_page = apply_filters( 'new_user_approve_show_admin_page', true );

        if ( $show_admin_page ) {
            $cap = apply_filters( 'new_user_approve_minimum_cap', 'edit_users' );
            add_users_page( __( 'Approve New Users', 'new-user-approve' ), __( 'Approve New Users', 'new-user-approve' ), $cap, $this->_admin_page, array( $this, 'approve_admin' ) );
        }
    }

    /**
     * Create the view for the admin interface
     */
    public function approve_admin() {
        if ( isset( $_GET['user'] ) && isset( $_GET['status'] ) ) {
            echo '<div id="message" class="updated fade"><p>'.__( 'User successfully updated.', 'new-user-approve' ).'</p></div>';
        }

        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'pending_users';
        ?>
        <div class="wrap">
            <h2><?php _e( 'User Registration Approval', 'new-user-approve' ); ?></h2>

            <h3 class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=pending_users' ) ); ?>" class="nav-tab<?php echo $active_tab == 'pending_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Users Pending Approval', 'new-user-approve' ); ?></span></a>
                <a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=approved_users' ) ); ?>" class="nav-tab<?php echo $active_tab == 'approved_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Approved Users', 'new-user-approve' ); ?></span></a>
                <a href="<?php echo esc_url( admin_url( 'users.php?page=new-user-approve-admin&tab=denied_users' ) ); ?>" class="nav-tab<?php echo $active_tab == 'denied_users' ? ' nav-tab-active' : ''; ?>"><span><?php _e( 'Denied Users', 'new-user-approve' ); ?></span></a>
            </h3>

            <?php if ( $active_tab == 'pending_users' ) : ?>
            <div id="pw_pending_users">
                <?php $this->user_table( 'pending' ); ?>
            </div>
            <?php elseif ( $active_tab == 'approved_users') : ?>
            <div id="pw_approved_users">
                <?php $this->user_table( 'approved' ); ?>
            </div>
            <?php elseif ( $active_tab == 'denied_users') : ?>
            <div id="pw_denied_users">
                <?php $this->user_table( 'denied' ); ?>
            </div>
            <?php endif; ?>
        </div>
    <?php
    }

    /**
     * Output the table that shows the registered users grouped by status
     *
     * @param string $status the filter to use for which the users will be queried. Possible values are pending, approved, or denied.
     */
    public function user_table( $status ) {
        global $current_user;

        $approve = ( 'denied' == $status || 'pending' == $status );
        $deny = ( 'approved' == $status || 'pending' == $status );

        $user_status = pw_new_user_approve()->get_user_statuses();
        $users = $user_status[$status];

        if ( count( $users ) > 0 ) {
            ?>
            <table class="widefat">
                <thead>
                <tr class="thead">
                    <th><?php _e( 'Username', 'new-user-approve' ); ?></th>
                    <th><?php _e( 'Name', 'new-user-approve' ); ?></th>
                    <th><?php _e( 'E-mail', 'new-user-approve' ); ?></th>
                    <?php if ( 'pending' == $status ) { ?>
                        <th colspan="2" style="text-align: center"><?php _e( 'Actions', 'new-user-approve' ); ?></th>
                    <?php } else { ?>
                        <th style="text-align: center"><?php _e( 'Actions', 'new-user-approve' ); ?></th>
                    <?php } ?>
                </tr>
                </thead>
                <tbody>
                <?php
                // show each of the users
                $row = 1;
                foreach ( $users as $user ) {
                    $class = ( $row % 2 ) ? '' : ' class="alternate"';
                    $avatar = get_avatar( $user->user_email, 32 );

                    $the_link = admin_url( sprintf( 'users.php?page=%s&user=%s&status=%s', $this->_admin_page, $user->ID, $status ) );
                    $the_link = wp_nonce_url( $the_link, 'pw_new_user_approve_action_' . get_class( $this ) );

                    if ( current_user_can( 'edit_user', $user->ID ) ) {
                        if ($current_user->ID == $user->ID) {
                            $edit_link = 'profile.php';
                        } else {
                            $edit_link = add_query_arg( 'wp_http_referer', urlencode( esc_url( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), "user-edit.php?user_id=$user->ID" );
                        }
                        $edit = '<strong><a href="' . esc_url( $edit_link ) . '">' . esc_html( $user->user_login ) . '</a></strong>';
                    } else {
                        $edit = '<strong>' . esc_html( $user->user_login ) . '</strong>';
                    }

                    ?><tr <?php echo $class; ?>>
                    <td><?php echo $avatar . ' ' . $edit; ?></td>
                    <td><?php echo get_user_meta( $user->ID, 'first_name', true ) . ' ' . get_user_meta( $user->ID, 'last_name', true ); ?></td>
                    <td><a href="mailto:<?php echo $user->user_email; ?>" title="<?php _e('email:', 'new-user-approve' ) ?> <?php echo $user->user_email; ?>"><?php echo $user->user_email; ?></a></td>
                    <?php if ( $approve && $user->ID != get_current_user_id() ) { ?>
                        <td align="center"><a href="<?php echo $the_link; ?>" title="<?php _e( 'Approve', 'new-user-approve' ); ?> <?php echo $user->user_login; ?>"><?php _e( 'Approve', 'new-user-approve' ); ?></a></td>
                    <?php } ?>
                    <?php if ( $deny && $user->ID != get_current_user_id() ) { ?>
                        <td align="center"><a href="<?php echo $the_link; ?>" title="<?php _e( 'Deny', 'new-user-approve' ); ?> <?php echo $user->user_login; ?>"><?php _e( 'Deny', 'new-user-approve' ); ?></a></td>
                    <?php } ?>
                    <?php if ( $user->ID == get_current_user_id() ) : ?>
                        <td colspan="2">&nbsp;</td>
                    <?php endif; ?>
                    </tr><?php
                    $row++;
                }
                ?>
                </tbody>
            </table>
        <?php
        } else {
            $status_i18n = $status;
            if ( $status == 'approved' ) {
                $status_i18n = __( 'approved', 'new-user-approve' );
            } else if ( $status == 'denied' ) {
                $status_i18n = __( 'denied', 'new-user-approve' );
            } else if ( $status == 'pending' ) {
                $status_i18n = __( 'pending', 'new-user-approve' );
            }

            echo '<p>'.sprintf( __( 'There are no users with a status of %s', 'new-user-approve' ), $status_i18n ) . '</p>';
        }
    }

    /**
     * Accept input from admin to modify a user
     */
    public function process_input() {
        if ( ( isset( $_GET['page'] ) && $_GET['page'] == $this->_admin_page ) && isset( $_GET['status'] ) ) {
            $valid_request = check_admin_referer( 'pw_new_user_approve_action_' . get_class( $this ) );

            if ( $valid_request ) {
                $status = $_GET['status'];
                $user_id = (int) $_GET['user'];

                do_action( 'new_user_approve_' . $status . '_user', $user_id );
            }
        }
    }

}

function pw_new_user_approve_admin_approve() {
    return pw_new_user_approve_admin_approve::instance();
}

pw_new_user_approve_admin_approve();
