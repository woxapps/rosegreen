<?php

/* -----------------------------------------------------------------------------

    VARIABLES

----------------------------------------------------------------------------- */

// Email where the contact form messages will be sent
$email_to = 'example@example.com';

// Email which will be set as the sender, it usually has to be from the same domain as website
$email_from = 'example@example.com';

// Default email subject (if the sender leaves subject field blank)
$default_email_subject = 'Contact form message';

// Text at the top of the email
$email_text = 'Form details below:';

// Sucess message
$success_msg = 'Message sent successfully!';

// Connection error message
$connection_error_msg = 'There was a connection problem. Try again later.';

// Required fields message
$required_fields_error_msg = 'Please make sure all required fields are filled out correctly.';

// Spam detected error message
$spam_error_message = 'Leave the field after the email field blank please.';

// Errors in form message
$multiple_errors_msg = 'Fix the following errors, please:';

// List of form fields
$form_fields = array(
    'contact-name' => array(
        'label' => 'Name',
        'type' => 'text',
        'required' => true,
        'validation_message' => 'The name you entered does not appear to be valid.',
    ),
    'contact-email' => array(
        'label' => 'Email',
        'type' => 'email',
        'required' => true,
        'validation_message' => 'The email address you entered does not appear to be valid.',
    ),
    'contact-phone' => array(
        'label' => 'Phone',
        'type' => 'text',
        'required' => false,
    ),
    'contact-subject' => array(
        'label' => 'Subject',
        'type' => 'text',
        'role' => 'subject', // This field will be used as email subject
        'required' => false,
    ),
    'contact-message' => array(
        'label' => 'Message',
        'type' => 'text',
        'role' => 'message', // This field will be used as main text of the email
        'required' => true,
        'validation_message' => 'The message you entered does not appear to be valid.',
    ),
);


/* -----------------------------------------------------------------------------

    FORM

----------------------------------------------------------------------------- */

if ( isset( $_REQUEST['contact-form'] ) ) {

    function lsvr_die( $error ) {

        echo json_encode( array(
            'type' => 'validation-error',
            'message' => $multiple_errors_msg . $error,
        ));

        die();
    }

	function lsvr_secure_string( $string ) {
		$string = strip_tags( $string );
		$string = htmlspecialchars( $string, ENT_QUOTES );
		$string = trim( $string );
		return $string;
	}

	/* -------------------------------------------------------------------------
        CHECK IF REQUIRED FIELDS EXIST
    ------------------------------------------------------------------------- */

    foreach ( $form_fields as $field_name => $field_data ) {

        if ( ! empty( $field_data['required'] ) && true === $field_data['required'] && ! isset( $_REQUEST[ $field_name ] ) ) {
            lsvr_die( $required_fields_error_msg );
        }

    }

    /* -------------------------------------------------------------------------
        CHECK HONEYPOT
    ------------------------------------------------------------------------- */

	if ( isset( $_REQUEST['form-email'] ) && $_REQUEST['form-email'] !== '' ) {

		lsvr_die( $spam_error_message );

	}

	/* -------------------------------------------------------------------------
        VALIDATE / PROCESS FIELDS
    ------------------------------------------------------------------------- */

	$error_message = array();
    $email_exp = '/^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/';

    // Parse all fields
    foreach ( $form_fields as $field_name => $field_data ) {

        // Check for required fields
        if ( ! empty( $field_data['required'] ) && true === $field_data['required'] ) {

            // Sanitize value
            $field_value = lsvr_secure_string( $_REQUEST[ $field_name ] );

            // Validate field
            if ( ( 'email' === $field_data['type'] && ! preg_match( $email_exp, $field_value ) ) ||
                strlen( $field_value ) < 1 ) {

                $error_message[] = ! empty( $field_data['validation_message'] ) ? $field_data['validation_message'] : $field_data['label'] . ' field is empty or not valid';

            }

        }

    }

	// Die if there is at least one valdiation error
    if ( ! empty( $error_message ) ) {

        lsvr_die( implode( '<br>', $error_message ) );

    }

	/* -------------------------------------------------------------------------
        SEND EMAIL
    ------------------------------------------------------------------------- */

    function lsvr_clean_string( $string ) {

        $remove = array( 'content-type', 'bcc:', 'to:', 'cc:', 'href' );

        return str_replace( $remove, '', $string );

    }

    // Compose email
    $email_message = $email_text . "\n\n";

    // Parse all fields
    foreach ( $form_fields as $field_name => $field_data ) {

        if ( isset( $_REQUEST[ $field_name ] ) ) {

            $field_value = lsvr_secure_string( $_REQUEST[ $field_name ] );

            if ( ! empty( $field_value ) ) {

                // Message role field
                if ( ! empty( $field_data['role'] ) && 'message' === $field_data['role'] ) {
                    $email_message .= "\n" . lsvr_clean_string( $field_value ) . "\n";
                }

                // Subject role field
                elseif ( ! empty( $field_data['role'] ) && 'subject' === $field_data['role'] ) {
                    $email_subject = $field_value;
                }

                // Other fields
                else {
                    $email_message .= $field_data['label'] . ': ' . lsvr_clean_string( $field_value ) . "\n";
                }

            }

        }

    }

    // Prepare headers
    $headers = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n" .
	'From: ' . $email_from . "\r\n" .
    'Reply-To: ' . $email_from . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

    // Prepare subject
    $email_subject = ! empty( $email_subject ) ? $email_subject : $default_email_subject;

    // Send email
	@mail( $email_to, '=?utf-8?B?' . base64_encode( $email_subject ) . '?=', $email_message, $headers );

	/* -------------------------------------------------------------------------
        OUTPUT SUCCESS MESSAGE
    ------------------------------------------------------------------------- */

    echo json_encode( array(
        'type' => 'success',
        'message' => $success_msg,
    ));

}

else {

	/* -------------------------------------------------------------------------
        CONNECTION PROBLEM MESSAGE
    ------------------------------------------------------------------------- */

	echo json_encode( array(
        'type' => 'connection-error',
        'message' => $connection_error_msg,
    ));

}

die();

?>