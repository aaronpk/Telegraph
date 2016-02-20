<?
use \Michelf\MarkdownExtra;
$this->layout('layout-loggedin', ['title' => $title, 'accounts' => $accounts, 'user' => $user, 'return_to' => $return_to]);
?>

<div class="ui main text container api-docs" style="margin-top: 80px;">

<h1>Superfeedr Integration</h1>
<? ob_start(); ?>

You can use Telegraph to receive Webmentions when your site is linked to by any website tracked by Superfeedr, even if that site doesn't send Webmentions itself!

<h2 class="ui dividing header">Setup</h2>

From the top right menu, click the "New Site" link.

<img src="/assets/superfeedr/create-new-website.png" class="tutorial-image">

Enter "Mentions" for the name (or anything you want, but that's what I use), and enter your home page URL. Entering your URL here is how Telegraph knows which links in the Superfeedr feed to send webmentions for.

<img src="/assets/superfeedr/new-site-form.png" class="tutorial-image">

After you create the site, click on the settings icon next to the name.

<img src="/assets/superfeedr/site-created.png" class="tutorial-image">

Near the bottom, there is a Superfeedr Webhook URL. Copy that URL since we'll need it in the next step.

<img src="/assets/superfeedr/site-settings.png" class="tutorial-image">

<h2 class="ui dividing header">Superfeedr Configuration</h2>

Now we need to sign up with Superfeedr and create a tracking feed. Create an account by visiting the [Superfeedr Tracker page](https://superfeedr.com/tracker). Make sure to choose "Tracker" from the account type dropdown. If you already have a Publisher or Subscriber account, you'll need to make a new Tracker account for this.

<img src="/assets/superfeedr/superfeedr-signup.png" class="tutorial-image">

Once you've signed up, you'll land on the Superfeedr dashboard. Click "Search and Track" to create a new tracker.

Enter `link:aaronparecki.com` as the query term, obviously replacing the domain with your own, and set the format to "json". Paste your Telegraph URL from the setup process into the Callback/Webhook URL field. Then click "Subscribe"!

<img src="/assets/superfeedr/superfeedr-configuration.png" class="tutorial-image">

At this point your tracker feed is live, and Superfeedr will begin sending web hooks to Telegraph whenever a new item is found that links to your website!

Unfortunately nothing will happen right away, so you'll have to wait for someone to publish a blog post that links to you. Check back in a little while and you should see some webmentions show up on your Telegraph dashboard!

<img src="/assets/superfeedr/telegraph-dashboard-mentions.png" class="tutorial-image">

Here you can see a few of the mentions I've received from my Superfeedr tracker, including one from Stack Overflow which doesn't yet send webmentions on its own!


<br>
<?
$source=ob_get_clean();
echo MarkdownExtra::defaultTransform($source);
?>

</div>
