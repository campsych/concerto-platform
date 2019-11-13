\name{concerto.event.remove}
\alias{concerto.event.remove}
\title{
    Events
}
\description{
    Removing events.
}
\usage{
    concerto.event.remove(name, fun)
}
\arguments{
    \item{name}{
        Event name
    }
    \item{fun}{
        Function
    }
}
\details{
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
    concerto.event.remove("onTemplateSubmit", function(response) {
        print(response)
    })
}

\keyword{ events }
