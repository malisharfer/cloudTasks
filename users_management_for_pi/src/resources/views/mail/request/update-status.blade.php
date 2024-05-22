Hi {{ $object->request->submit_username }}, 

<br/> 
@if ( $object->request->status->value != 'new')

    The request for a new user: <b> {{ $object->request->fullname }} </b> has been {{ $object->request->status->value }}

@elseif (!$object->user_datails->password)
    @if($object->user_datails->password=="")
        The user <b> {{ $object->user_datails->username }} </b> is already exists,please handle this issue manually.
    @else
        Something went wrong , while trying to add the user : <b> {{ $object->user_datails->username }} </b>
    @endif
@endif


<br/> 

@if ( $object->request->status->value == 'approved')

    User <b> {{ $object->request->fullname }} </b> successfully added 

    <br/>

    The username is: <b> {{ $object->user_datails->username }} </b>

    <br/>

    The password is: <b> {{ $object->user_datails->password }} </b>

@endif