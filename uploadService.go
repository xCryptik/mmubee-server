//CGO_ENAMLED=0 GOOS=linux GOARCH=amd64 go build upload.go; scp -r ./upload root@10.0.1.14:/home/MMUbee/go
package main

import (
	"crypto/md5"
	"database/sql"
	"encoding/hex"
	"flag"
	"image"
	"image/jpeg"
	"io"
	"log"
	"net/http"
	"os"
	"runtime"

	_ "github.com/go-sql-driver/mysql"
	"github.com/nfnt/resize"
	//"time"
)

var (
	addr  = flag.String("addr", ":7000", "http service address")
	mysql *sql.DB
)

func init() {
	runtime.GOMAXPROCS(runtime.NumCPU())

	//connect to mysqlserver
	var err error

	mysql, err = sql.Open("mysql", "root:@/mmubee")
	if err != nil {
		log.Fatalln("sql.Open:", err)
	}
}

func main() {
	http.HandleFunc("/upload", photoUploadHandler)
	log.Fatalln(http.ListenAndServe(*addr, nil))
}

func check(err error) {
	if err != nil {
		log.Println(err)
	}
}

func fileHash(fp io.Reader) string {
	hash := md5.New()
	io.Copy(hash, fp)
	return hex.EncodeToString(hash.Sum(nil))
}

func photoUploadHandler(w http.ResponseWriter, r *http.Request) {
	//time.Sleep(10 * time.Second)
	//time.Sleep(time.Second * 5)

	r.ParseMultipartForm(32 << 20) //最大缓存
	//log.Println(r.MultipartForm)
	if r.MultipartForm != nil && r.MultipartForm.File != nil {

		//open file
		file, header, err := r.FormFile("file")
		check(err)
		defer file.Close()
		fileForHash, _, err := r.FormFile("file")
		check(err)
		defer fileForHash.Close()

		//get image hash
		hash := fileHash(fileForHash)
		filename := "upload/photo/" + hash + ".jpg"
		//log.Println(hash)

		//check if original image has been ever uploaded
		var photoHash, photoOriginalHash []byte
		stmt, err := mysql.Prepare("SELECT photo_hash,photo_original_hash FROM photo where photo_original_hash = ? or photo_hash = ?")
		defer stmt.Close()
		err = stmt.QueryRow(hash, hash).Scan(&photoHash, &photoOriginalHash)

		if photoHash != nil {
			log.Println("photo_original_hash or photo_hash exists")

			//save to database
			stmtIns, err := mysql.Prepare("INSERT INTO photo(photo_ip, photo_hash, photo_original_hash, photo_object_id, photo_filename) VALUES(?,?,?,?,?)")
			check(err)
			defer stmtIns.Close()
			_, err = stmtIns.Exec(r.RemoteAddr, photoHash, photoOriginalHash, r.Header["Mmubee-Uuid"][0], header.Filename)

			w.Write(photoHash)
			return
		}

		//save file on server
		writer, err := os.Create(filename)
		defer writer.Close()
		check(err)
		// if err == nil {
		// 	io.Copy(writer, file)
		// }

		//decode original image
		image, _, err := image.Decode(file)
		if err != nil {
			w.Write([]byte("incorrect format"))
			return
		}
		//resize image to below 1024*768
		newImage := resize.Thumbnail(1024, 768, image, resize.Lanczos3)
		//encode image to jpeg format and save it
		err = jpeg.Encode(writer, newImage, nil)
		check(err)

		//get new image hash
		resizedImage, _ := os.Open(filename)
		resizedImageHash := fileHash(resizedImage)
		defer resizedImage.Close()
		newFilename := "upload/photo/" + resizedImageHash + ".jpg"
		os.Rename(filename, newFilename)

		//save to database
		stmtIns, err := mysql.Prepare("INSERT INTO photo(photo_ip, photo_hash, photo_original_hash, photo_object_id, photo_filename) VALUES(?,?,?,?,?)")
		check(err)
		defer stmtIns.Close()

		//log.Println(r.Header)
		//excute insertion
		_, err = stmtIns.Exec(r.RemoteAddr, resizedImageHash, hash, r.Header["Mmubee-Uuid"][0], header.Filename)
		w.Write([]byte(resizedImageHash))

		//insert record
		if err != nil {
			log.Println(err)
		}

		thumbanil(newFilename, resizedImageHash)
	}

}

func thumbanil(filename, resizedImageHash string) {
	file, err := os.Open(filename)
	if err != nil {
		log.Fatal(err)
	}

	// decode jpeg into image.Image
	img, err := jpeg.Decode(file)
	if err != nil {
		log.Fatal(err)
	}
	file.Close()

	// resize to width 1000 using Lanczos resampling
	// and preserve aspect ratio
	m := resize.Thumbnail(120, 120, img, resize.Lanczos3)

	out, err := os.Create("upload/thumbanil/" + resizedImageHash + ".jpg")
	if err != nil {
		log.Fatal(err)
	}
	defer out.Close()

	// write new image to file
	jpeg.Encode(out, m, nil)

}
