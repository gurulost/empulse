<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Companies;

// Історія оплачених рахунків тощо.
// Завантажуйте звіти (PDF або інші формати)

class WorkfitAdminController extends Controller
{
    public function getCompanyList(){
        $companiesModel = new Companies();
        $list = $companiesModel->getCompanyList();

        return view('workfit_admin.company.list', [
            'list' => $list
        ]);
    }

    public function getCompany(Request $request, $id){
        $companiesModel = new Companies();
        $list = $companiesModel->getCompanyUsers($id);

        return view('workfit_admin.company.item', [
            'list' => $list
        ]);
    }

    public function deleteUser(Request $request, $id){
        $companiesModel = new Companies();
        $companiesModel->deleteUser($id);

        return redirect()->back();
    }
    public function getSubscriptionList(){
        $companiesModel = new Companies();
        $list = $companiesModel->getSubscriptionList();

        return view('workfit_admin.subscription.list', [
            'list' => $list
        ]);
    }

    public function getUsersList(){
        var_dump('hio');
    }
}
