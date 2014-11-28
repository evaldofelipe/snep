<?php

/**
 *  This file is part of SNEP.
 *
 *  SNEP is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SNEP is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SNEP.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Expression Alias Controller
 *
 * @category  Snep
 * @package   Snep
 * @copyright Copyright (c) 2014 OpenS Tecnologia
 * @author    Opens Tecnologia <desenvolvimento@opens.com.br>
 */
class ExpressionAliasController extends Zend_Controller_Action {

    /**
     *
     * @var Zend_Form
     */
    protected $form;

    /**
     * indexAction
     */
    public function indexAction() {
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Routing"),
                    $this->view->translate("Expression Alias")));

        $aliases = PBX_ExpressionAliases::getInstance();
        $this->view->aliases = $aliases->getAll();

        $this->view->filter = array(array("url" => $this->getFrontController()->getBaseUrl() . '/' . $this->getRequest()->getControllerName() . '/add',
                "display" => $this->view->translate("Add Expression Alias"),
                "css" => "include"),
        );
    }

    /**
     * getForm
     * @return <obj>
     */
    protected function getForm() {

        if ($this->form === Null) {
            $form_xml = new Zend_Config_Xml(Zend_Registry::get("config")->system->path->base . "/modules/default/forms/expression_alias.xml");
            $form = new Snep_Form($form_xml);


            $exprField = new Snep_Form_Element_Html("expression-alias/elements/expr.phtml", "expr", false);
            $exprField->setLabel($this->view->translate("Expressions"));
            $exprField->setOrder(1);
            $form->addElement($exprField);

            $this->form = $form;
        }
        return $this->form;
    }

    /**
     * AddAction - Add expression alias
     * @throws PBX_Exception_BadArg
     */
    public function addAction() {
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Routing"),
                    $this->view->translate("Expression Alias"),
                    $this->view->translate("Add Expression Alias")));

        $form = $this->getForm();
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            $expression = array(
                "name" => $_POST['name'],
                "expressions" => explode(",", $_POST['exprValue'])
            );
            $aliasesPersistency = PBX_ExpressionAliases::getInstance();

            //validation
            $valida = Snep_ValidateExpression::execute($_POST['exprValue']);

            if ($valida['status'] != false) {

                $this->view->message = $this->view->translate("Their alias has invalid character. Accents are not allowed, empty value between the keys, blanks and special characters except( # % | . - _ )");
                $this->view->msgclass = 'failure';
                header("refresh:7; ../expression-alias/add");
            } else
            if ($_POST["name"] == "" || $_POST["exprValue"] == "") {
                $this->view->message = $this->view->translate("Required value");
                $this->view->msgclass = 'failure';

                header("refresh:2; ../expression-alias/add");
            } else {
                try {

                    $aliasesPersistency->register($expression);
                    $exprList = $expression['expressions'];
                    $expr = "exprObj.addItem(" . count($exprList) . ");\n";

                    foreach ($exprList as $index => $value) {
                        $expr .= "exprObj.widgets[$index].value='{$value}';\n";
                    }

                    $this->view->dataExprAlias = $expr;
                    $form = $this->getForm();
                    $form->getElement('name')->setValue($_POST['name']);
                } catch (Exception $ex) {
                    throw new PBX_Exception_BadArg("Invalid Argument");
                }
                $this->_helper->redirector('index');
            }
        } else {
            $this->view->dataExprAlias = "exprObj.addItem();\n";
        }

        $this->renderScript('expression-alias/add.phtml');
    }

    /**
     * editAction - Edit expression alias
     */
    public function editAction() {
        $id = (int) $this->getRequest()->getParam('id');
        $this->view->breadcrumb = Snep_Breadcrumb::renderPath(array(
                    $this->view->translate("Routing"),
                    $this->view->translate("Expression Alias"),
                    $this->view->translate("Edit Expression Alias %s", $id)));

        $form = $this->getForm();
        $this->view->form = $form;
        $aliasesPersistency = PBX_ExpressionAliases::getInstance();

        if ($this->getRequest()->isPost()) {
            $expression = array(
                "id" => $id,
                "name" => $_POST['name'],
                "expressions" => explode(",", $_POST['exprValue'])
            );

            //validation
            $valida = Snep_ValidateExpression::execute($_POST['exprValue']);

            if ($valida['status'] != false) {

                $this->view->message = $this->view->translate("Their alias has invalid character. Accents are not allowed, empty value between the keys, blanks and special characters except( # % | . - _ )");
                $this->view->msgclass = 'failure';
                header("refresh:7; ../expression-alias/edit/id/$id");
            } else
            if ($_POST["name"] == "" || $_POST["exprValue"] == "") {
                $this->view->message = $this->view->translate("Required value");
                $this->view->msgclass = 'failure';

                header("refresh:2; ../expression-alias/edit/id/$id");
            } else {

                try {
                    $aliasesPersistency->update($expression);
                } catch (Exception $ex) {
                    display_error($ex->getMessage(), true);
                }
                $this->_forward('index', 'expression-alias');
            }
        } else {

            $alias = $aliasesPersistency->get($id);
            $exprList = $alias['expressions'];
            $expr = "exprObj.addItem(" . count($exprList) . ");\n";

            foreach ($exprList as $index => $value) {
                $expr .= "exprObj.widgets[$index].value='{$value}';\n";
            }
            $this->view->dataExprAlias = $expr;
            $form = $this->getForm();
            $form->getElement('name')->setValue($alias['name']);

            $this->renderScript('expression-alias/edit.phtml');
        }
    }

    /**
     * deleteAction - Delete expression alias
     */
    public function deleteAction() {

        if ($this->getRequest()->isGet()) {
            $id = (int) $this->getRequest()->getParam('id');
            $aliasesPersistency = PBX_ExpressionAliases::getInstance();

            //verifica se grupo é usado em alguma regra
            $regras = $aliasesPersistency->getValidation($id);

            if (count($regras) > 0) {

                $this->view->error = $this->view->translate("Cannot remove. The following routes are using this expression alias: ") . "<br />";
                foreach ($regras as $regra) {
                    $this->view->error .= $regra['id'] . " - " . $regra['desc'] . "<br />\n";
                }

                $this->_helper->viewRenderer('error');
            } else {

                $alias = $aliasesPersistency->get($id);
                if ($alias !== null) {
                    $aliasesPersistency->delete($id);
                }
                $this->_forward('index', 'expression-alias');
            }
        }
    }

}
