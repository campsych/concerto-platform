\name{concerto.template.show}
\alias{concerto.template.show}
\title{
Show template to test participant.
}
\description{
Display template to test participant.
}
\usage{
concerto.template.show(templateId=-1, html="", head="", params = list(), timeLimit = 0, finalize = F)
}

\arguments{
  \item{templateId}{
Template ID or name.
}

 \item{html}{
Template html.
}

\item{head}{
Template head tag.
}

  \item{params}{
Named list with parameters we want to be inserted into template html.
}
  \item{timeLimit}{
Time limit after which template will be auto submitted (in seconds).
}
  \item{finalize}{
Logical: TRUE if after this template there is no more logic available.
}

}
\details{
}
\value{
Returns named list with template specific input gathered from user and static variables:
\item{buttonPressed}{character: name of the button that submitted the template }
\item{isTimeout }{numeric: 1 if time limit has been reached and 0 if it hasn't}
\item{timeTaken }{numeric: time taken to submit the template}
}
\references{
}
\author{
Przemyslaw Lis
}
\note{
}

\seealso{
}
\examples{
response1 <- concerto.template.show(1)
response2 <- concerto.template.show(html="<div align="center">Hello world!</div>")
}

\keyword{ template }
\keyword{ HTML }
