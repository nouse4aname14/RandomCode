<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MigrateProducts extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:products';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Migrates Products from Sites to Users.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		//
        $users = User::with("sites")->get();

        $lookup = array();
        foreach ($users as $user)
        {
            foreach($user->sites as $site)
            {
                $lookup[$site->id] = $user->id;
            }
        }

        $streams = Product::all();
        foreach($streams as $stream)
        {
            $stream->user_id = $lookup[$stream->site_id];
            $stream->save();
        }
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			//array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			//array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

}