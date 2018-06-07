<?php
namespace App\Controller;

use App\Entity\User;
use Denpa\Bitcoin\Client as BitcoinClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NotifyController Extends Controller 
{
    /**
     * @Route("/notify/{txid}", name="user_notify")
     */
    public function walletNotify($txid, \Swift_Mailer $mailer){

        $bitcoind = new BitcoinClient('http://username:password@localhost:8332/');

        $aTransaction = $bitcoind->GetTransaction($txid)->get();

        $sConfirmations = $aTransaction['confirmations'];
        $sTXID = $aTransaction['txid'];

        $sCategory = $aTransaction['details'][0]['category'];
        $sAccount = $aTransaction['details'][0]['account'];
        $sAddress = $aTransaction['details'][0]['address'];
        $sAmount = $aTransaction['details'][0]['amount'];

        //When doing a transfer on the blockchain from one account to another, both the receive and sent details are included.
        //The sent details will be at [0] in this case, receive at [1]
        //If the account is empty, and category is send, then we should reference the receive part under [1].
        //When receiving a transaction from another wallet, the details will only contain the receive details under [0]
        if($sAccount == "" && $sCategory == "send"){ 
            $sAccount = $aTransaction['details'][1]['account'];
            $sCategory = $aTransaction['details'][1]['category'];
            $sAddress = $aTransaction['details'][1]['address'];
            $sAmount = $aTransaction['details'][1]['amount'];
        }

        $message = (new \Swift_Message($sCategory.' transaction'))
            ->setFrom('hello@peterdalby.me')
            ->setTo($sAccount)
            ->setBody(
                $this->renderView(
                    'emails/transactionNotify.html.twig',
                    [
                        'category' => $sCategory,
                        'txid' => $sTXID,
                        'account' => $sAccount,
                        'amount' => $sAmount,
                        'confirmations' => $sConfirmations,
                        'address' => $sAddress,
                    ]
                ),
                'text/html'
            )
        ;
    
    if($sCategory == "receive" && $sConfirmations == 1){ // The balance is only added for receives, when 1 confirmation is reached.
        
        $mailer->send($message); //Sends email at 1 confirmation for receives.

            //If the account is specified in the TXID, it means that a match of address was automatically found.
            $oUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $sAccount]);
            
            if (!$oUser) {
                throw $this->createNotFoundException(
                    'No user was found for the address'
                );
            }

            $sBalance = $oUser->getBalance();

            $entityManager = $this->getDoctrine()->getManager();
            $oUser->setBalance($sBalance + $sAmount);
            $entityManager->flush();
            return $this->redirectToRoute('user_wallet');
    }
    return $this->redirectToRoute('user_wallet');
    }

    /**
     * @Route("/block/notify/{block}", name="block_notify")
     */
    
    public function blockNotify($block, \Swift_Mailer $mailer){

        $bitcoind = new BitcoinClient('http://username:password@localhost:8332/');

        $blockinfo = $bitcoind->getblock($block)->get();

        $message = (new \Swift_Message('New block found!'))
        ->setFrom('hello@peterdalby.me')
        ->setTo('hello@peterdalby.me')
        ->setBody(
            $this->renderView(
                'emails/blockNotify.html.twig',
                [
                    'size' => $blockinfo['size'],
                    'confirmations' => $blockinfo['confirmations'],
                    'difficulty' => $blockinfo['difficulty'],
                    'hash' => $blockinfo['hash'],
                    'time' => $blockinfo['time'],
                ]
            ),
            'text/html'
        )
    ;
        $mailer->send($message);
        return $this->redirectToRoute('user_wallet');
}
}


?>