<?php
declare(strict_types=1);
/**
 * /src/Controller/UserController.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Controller;

use App\Resource\UserResource;
use App\Rest\Controller;
use App\Rest\ResponseHelperInterface;
use App\Rest\Traits\Actions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/** @noinspection PhpHierarchyChecksInspection */
/** @noinspection PhpMissingParentCallCommonInspection */

/**
 * Class UserController
 *
 * @Route(path="/user")
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 *
 * @package App\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method UserResource getResource()
 */
class UserController extends Controller
{
    // Traits for REST actions
    use Actions\Root\FindAction;
    use Actions\Root\FindOneAction;

    /**
     * UserController constructor.
     *
     * @param UserResource            $resource
     * @param ResponseHelperInterface $responseHelper
     */
    public function __construct(UserResource $resource, ResponseHelperInterface $responseHelper)
    {
        $this->init($resource, $responseHelper);
    }
}