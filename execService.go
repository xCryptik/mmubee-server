package main

import (
	"log"
	"os/exec"
	"time"
)

func main() {
	//check php
	path, err := exec.LookPath("php")
	if err != nil {
		log.Fatalln("php was not found.")
	}

	for {
		//mmls
		mmls := "mmlsService.php"
		out, err := exec.Command("php", mmls).Output()
		if err != nil {
			log.Println(err)
		}
		log.Println(path, mmls, " - ", string(out[:]))

		//news
		news := "newsService.php"
		out, err = exec.Command("php", news).Output()
		if err != nil {
			log.Println(err)
		}
		log.Println(path, news, " - ", string(out[:]))

		time.Sleep(time.Hour * 2)
		//time.Sleep(time.Second * 30)
	}
}
