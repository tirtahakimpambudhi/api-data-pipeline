<?php

use App\Constants\RolesTypes;

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

//it('returns a permission roles', function () {
//    dd(collect(RolesTypes::permissions(RolesTypes::SLAVE))->toPrettyJson());
////    dd(collect(RolesTypes::permissions(RolesTypes::ALMIGHTY))->toPrettyJson());
//});
